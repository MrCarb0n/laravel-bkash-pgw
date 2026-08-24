<?php

namespace Tiash\LaravelBkash\Events;

final class WebhookEvent
{
    public const PAYMENT_API = '10002294';
    public const PAYMENT_QR = '10003126';
    public const PAYMENT_USSD = '10002175';
    public const REDEEM_VOUCHER = '10002809';
    public const M2M_TRANSFER_API = '10002264';
    public const M2M_TRANSFER_QR = '10003209';
    public const M2M_TRANSFER_USSD = '10002177';
    public const PAYMENT_BANK = '10003476';
    public const B2B_COLLECTION_WALLET_TO_MERCHANT = '10003237';
    public const DISTRIBUTOR_TO_B2B_COLLECTION_USSD = '10003236';
    public const DSO_TO_MERCHANT_B2BC_API = '10004036';

    public static function getDescription(string $type): string
    {
        return match ($type) {
            self::PAYMENT_API => 'Payment via API',
            self::PAYMENT_QR => 'Payment via QR',
            self::PAYMENT_USSD => 'Payment via USSD',
            self::REDEEM_VOUCHER => 'Redeem Voucher',
            self::M2M_TRANSFER_API => 'M2M Transfer via API',
            self::M2M_TRANSFER_QR => 'M2M Transfer via QR',
            self::M2M_TRANSFER_USSD => 'M2M Transfer via USSD',
            self::PAYMENT_BANK => 'Payment via Bank',
            self::B2B_COLLECTION_WALLET_TO_MERCHANT => 'B2B Collection Wallet to Merchant Plus (BC2M)',
            self::DISTRIBUTOR_TO_B2B_COLLECTION_USSD => 'Distributor to B2B Collection Wallet Transfer via USSD (D2BC)',
            self::DSO_TO_MERCHANT_B2BC_API => 'DSO to Merchant Plus-B2BC via API',
            default => 'Unknown transaction type',
        };
    }

    public static function isPayment(string $type): bool
    {
        return in_array($type, [
            self::PAYMENT_API,
            self::PAYMENT_QR,
            self::PAYMENT_USSD,
            self::REDEEM_VOUCHER,
            self::PAYMENT_BANK,
        ], true);
    }

    public static function isM2M(string $type): bool
    {
        return in_array($type, [
            self::M2M_TRANSFER_API,
            self::M2M_TRANSFER_QR,
            self::M2M_TRANSFER_USSD,
        ], true);
    }

    public static function isB2B(string $type): bool
    {
        return in_array($type, [
            self::B2B_COLLECTION_WALLET_TO_MERCHANT,
            self::DISTRIBUTOR_TO_B2B_COLLECTION_USSD,
            self::DSO_TO_MERCHANT_B2BC_API,
        ], true);
    }
}