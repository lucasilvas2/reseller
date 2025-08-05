<?php

namespace App\Enums;

enum SaleEnum
{
    case Pending;
    case Paid;
    case Canceled;
    case Refunded;
    case Failed;
    case Processing;
    case Completed;
    case OnHold;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Pago',
            self::Canceled => 'Cancelado',
            self::Refunded => 'Reembolsado',
            self::Failed => 'Falhou',
            self::Processing => 'Processando',
            self::Completed => 'Concluído',
            self::OnHold => 'Em Espera',
        };
    }

    public function value(): string
    {
        return $this->value;
    }
}
