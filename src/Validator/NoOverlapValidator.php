<?php

namespace App\Validator;

use App\Repository\AppointmentRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoOverlapValidator extends ConstraintValidator
{
    private AppointmentRepository $appointmentRepository;

    public function __construct(AppointmentRepository $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoOverlap) {
            throw new UnexpectedTypeException($constraint, NoOverlap::class);
        }

        if (!$value instanceof \App\Entity\Appointment) {
            return;
        }

        $overlapping = $this->appointmentRepository->findOverlapping($value);

        if (count($overlapping) > 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('heureRdv')
                ->addViolation();
        }
    }
}
