<?php

namespace App\Services;

final class Money
{
    public static function cents(mixed $value): int
    {
        $value=is_float($value)?sprintf('%.2F',$value):(string)$value;$negative=str_starts_with($value,'-');$value=ltrim($value,'-');
        if(!preg_match('/^\d+(?:\.\d{1,2})?$/',$value))throw new \InvalidArgumentException('Importe decimal inválido.');
        [$whole,$fraction]=array_pad(explode('.',$value,2),2,'');$cents=(int)$whole*100+(int)str_pad($fraction,2,'0');
        return $negative?-$cents:$cents;
    }
    public static function decimal(int $cents): string {return ($cents<0?'-':'').intdiv(abs($cents),100).'.'.str_pad((string)(abs($cents)%100),2,'0',STR_PAD_LEFT);}
    public static function sum(iterable $values): string {$total=0;foreach($values as $v)$total+=self::cents($v??'0');return self::decimal($total);}
    public static function format(mixed $value): string
    {
        $cents=self::cents($value);$whole=(string)intdiv(abs($cents),100);
        return ($cents<0?'-':'').preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole).','.str_pad((string)(abs($cents)%100),2,'0',STR_PAD_LEFT);
    }
}
