<?php

declare(strict_types=1);

namespace ITB\ApiPlatformUpdateActionsBundle\Validation;

use ITB\ApiPlatformUpdateActionsBundle\Action\ActionCollectionException\ActionForResourceNotFound;
use ITB\ApiPlatformUpdateActionsBundle\Command\CommandFactory;
use ITB\ApiPlatformUpdateActionsBundle\Exception\RuntimeExceptionInterface;
use ITB\ApiPlatformUpdateActionsBundle\Request\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UpdateRequestValidator extends ConstraintValidator
{
    /**
     * @param CommandFactory $commandFactory
     */
    public function __construct(private CommandFactory $commandFactory)
    {
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     * @throws RuntimeExceptionInterface
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UpdateRequest) {
            throw new UnexpectedTypeException($constraint, UpdateRequest::class);
        }

        if (!$value instanceof Request) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, Request::class);
        }

        try {
            $this->commandFactory->createCommand($value);
        } catch (ActionForResourceNotFound $exception) {
            // The resource is checked for null value in the createCommand method.
            /** @var string $resource */
            $resource = $value->resource;

            $this->context->buildViolation($constraint->actionUnknownMessage)
                ->setParameter('{{ action }}', $value->action)
                ->setParameter('{{ resource }}', $resource)
                ->atPath('action')
                ->addViolation();
        } catch (ExceptionInterface $exception) {
            $this->context->buildViolation($constraint->denormalizationFailedMessage)
                ->atPath('payload')
                ->addViolation();
        }
    }
}
