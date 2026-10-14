<?php

namespace App\Services;

use CodeIgniter\Shield\Entities\User;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class FirstCollectionDateService
{
    public static function today(): string
    {
        return (new DateTimeImmutable('today', new DateTimeZone('America/Buenos_Aires')))->format('Y-m-d');
    }

    public function fromForm(bool $enabled, string $date, ?User $actor): ?string
    {
        if (!$enabled) return null;
        if ($actor === null || !$actor->can('applications.schedule')) {
            throw new InvalidArgumentException('No tenés permiso para definir una fecha de cobro.');
        }
        return $this->validate($date);
    }

    public function validate(string $date, ?string $today = null): string
    {
        $date = trim($date);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Seleccioná una fecha válida para el cobro.');
        }
        if ($date < ($today ?? self::today())) {
            throw new InvalidArgumentException('La fecha de cobro quedó en el pasado. Corregila antes de aprobar la solicitud.');
        }
        return $date;
    }
}
