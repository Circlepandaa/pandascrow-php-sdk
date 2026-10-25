<?php

namespace Pandascrow\Tests;

use Pandascrow\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every helper must hit the right method + path with the field names the API reads.
 */
class ResourcesTest extends TestCase
{
    /** @var FakeTransport */
    private static $http;
    /** @var Client */
    private static $ps;

    public static function setUpBeforeClass(): void
    {
        self::$http = new FakeTransport();
        self::$ps   = new Client('pk_test', ['transport' => self::$http]);
    }

    /** @return array<string, array{0: callable, 1: string, 2: string, 3: array<string, mixed>}> */
    public static function calls(): array
    {
        $bank = ['account_number' => '0123456789', 'account_name' => 'Ada', 'bank_code' => '058'];
        return [
            'auth.login'           => [function (Client $c) { return $c->auth->login('a@b.c', 'pw'); }, 'POST', '/login', ['email' => 'a@b.c', 'password' => 'pw']],
            'auth.verifyLoginOtp'  => [function (Client $c) { return $c->auth->verifyLoginOtp('a@b.c', '1234'); }, 'POST', '/login/otp', ['otp_code' => '1234']],

            'escrow.createOnetime' => [function (Client $c) { return $c->escrow->createOnetime(['uuid' => 'u', 'metadata' => ['property_id' => 'P1']]); }, 'POST', '/escrow/initialize', ['escrow_type' => 'onetime', 'metadata' => ['property_id' => 'P1']]],
            'escrow.createMilestone' => [function (Client $c) { return $c->escrow->createMilestone(['uuid' => 'u']); }, 'POST', '/escrow/initialize', ['escrow_type' => 'milestone']],
            'escrow.createRecurring' => [function (Client $c) { return $c->escrow->createRecurring(['uuid' => 'u']); }, 'POST', '/escrow/initialize', ['escrow_type' => 'recurring']],
            'escrow.all'           => [function (Client $c) { return $c->escrow->all('u'); }, 'GET', '/escrow', ['uuid' => 'u']],
            'escrow.find'          => [function (Client $c) { return $c->escrow->find('u', 3); }, 'GET', '/escrow/single', ['uuid' => 'u', 'escrow_id' => '3']],
            'escrow.complete'      => [function (Client $c) { return $c->escrow->complete('u', '3', '9999'); }, 'POST', '/escrow/complete', ['escrow_id' => 3, 'otp' => '9999']],
            'escrow.resendOtp'     => [function (Client $c) { return $c->escrow->resendReleaseOtp('u', 3); }, 'POST', '/escrow/resend-otp', ['escrow_id' => 3]],
            'escrow.nextMilestone' => [function (Client $c) { return $c->escrow->payNextMilestone('u', 3); }, 'POST', '/escrow/next-payment', ['escrow_id' => 3]],
            'escrow.completeMs'    => [function (Client $c) { return $c->escrow->completeMilestone('u', 3, 4, '1111'); }, 'POST', '/escrow/milestone-item/complete', ['milestone_id' => 4, 'otp' => '1111']],
            'escrow.regenerate'    => [function (Client $c) { return $c->escrow->regeneratePayment('u', 3); }, 'POST', '/escrow/regenerate-payment', ['escrow_id' => 3]],
            'escrow.regenerateMs'  => [function (Client $c) { return $c->escrow->regenerateMilestonePayment('u', 3, 4); }, 'POST', '/escrow/regenerate-milestone-payment', ['milestone_id' => 4]],
            'escrow.dispute'       => [function (Client $c) { return $c->escrow->dispute('u', 3, 'Not delivered'); }, 'POST', '/escrow/dispute', ['reason' => 'Not delivered']],
            'escrow.disputes'      => [function (Client $c) { return $c->escrow->disputes('u', 3); }, 'GET', '/escrow/dispute', ['escrow_id' => '3']],
            'escrow.joinRecurring' => [function (Client $c) { return $c->escrow->joinRecurring('u', 3, 'Ada', 'a@b.c', 5000); }, 'POST', '/escrow/recurring/join', ['name' => 'Ada', 'email' => 'a@b.c']],

            'wallet.list'          => [function (Client $c) { return $c->wallet->list('u'); }, 'GET', '/wallets', ['uuid' => 'u']],
            'wallet.balances'      => [function (Client $c) { return $c->wallet->balances('u'); }, 'GET', '/wallet/balance', ['uuid' => 'u']],
            'wallet.transactions'  => [function (Client $c) { return $c->wallet->transactions('u', 'ngn'); }, 'GET', '/wallet', ['currency' => 'NGN']],
            'wallet.accounts'      => [function (Client $c) { return $c->wallet->accounts('u'); }, 'GET', '/wallet/account', ['uuid' => 'u']],
            'wallet.payout'        => [function (Client $c) use ($bank) { return $c->wallet->payout('u', 1000, 'NGN', $bank); }, 'POST', '/wallet/payout', ['method' => 'bank_transfer', 'destination' => json_encode($bank)]],

            'payouts.create'       => [function (Client $c) { return $c->payouts->create('u', 100, 'usd', 7); }, 'POST', '/payouts', ['currency' => 'USD', 'settlement_account_id' => 7]],
            'payouts.all'          => [function (Client $c) { return $c->payouts->all('u', ['status' => 'pending']); }, 'GET', '/payouts', ['status' => 'pending']],
            'payouts.settlement'   => [function (Client $c) { return $c->payouts->settlementAccounts('u'); }, 'GET', '/setting/settlement', ['uuid' => 'u']],

            'bank.banks'           => [function (Client $c) { return $c->bank->banks(); }, 'GET', '/bank/lists', []],
            'bank.validate'        => [function (Client $c) { return $c->bank->validate('u', '058', '0123456789'); }, 'POST', '/bank/validate', ['bank_code' => '058', 'account_number' => '0123456789']],
            'bank.transfer'        => [function (Client $c) use ($bank) { return $c->bank->transfer('u', 5000, $bank, 'NGN', ['idempotency_key' => 'K1']); }, 'POST', '/bank/transfers', ['bank' => $bank, 'idempotency_key' => 'K1', 'amount' => '5000']],
            'bank.settlement'      => [function (Client $c) { return $c->bank->settlementAccount('u'); }, 'GET', '/bank/settlement', ['uuid' => 'u']],

            'dva.createDynamic'    => [function (Client $c) { return $c->virtualAccounts->createDynamic('u', 'Ada', 5000, ['expiry_hours' => 2, 'metadata' => ['customer_id' => 'C1']]); }, 'POST', '/dva/create', ['customer' => ['account' => ['name' => 'Ada', 'expiry' => ['hours' => 2]]], 'order' => ['amounttype' => 'EXACT', 'amount' => 5000], 'metadata' => ['customer_id' => 'C1']]],
            'dva.createStaticAny'  => [function (Client $c) { return $c->virtualAccounts->createStatic('u', 'Ada'); }, 'POST', '/dva/static/create', ['order' => ['amounttype' => 'ANY']]],
            'dva.reallocate'       => [function (Client $c) { return $c->virtualAccounts->reallocate('u', '555', 'Ada', 100); }, 'POST', '/dva/reallocate', ['customer' => ['account' => ['name' => 'Ada', 'number' => '555']]]],
            'dva.deactivate'       => [function (Client $c) { return $c->virtualAccounts->deactivate('u', '555'); }, 'POST', '/dva/deactivate', ['customer' => ['account' => ['number' => '555']]]],
            'dva.reactivate'       => [function (Client $c) { return $c->virtualAccounts->reactivate('u', '555'); }, 'POST', '/dva/reactivate', ['customer' => ['account' => ['number' => '555']]]],
            'dva.confirmPayment'   => [function (Client $c) { return $c->virtualAccounts->confirmPayment('u', '555', 'REF', 100); }, 'POST', '/dva/confirm-payment', ['accountnumber' => '555', 'reference' => 'REF']],

            'invoices.create'      => [function (Client $c) { return $c->invoices->create('u', ['email' => 'c@d.e'], ['due_date' => '2026-12-01'], [['description' => 'x', 'quantity' => 1, 'unit_price' => 10]], 'payment_link', ['metadata' => ['order_id' => 'A1']]); }, 'POST', '/invoice', ['owner_uuid' => 'u', 'payment' => ['method' => 'payment_link'], 'metadata' => ['order_id' => 'A1']]],
            'invoices.all'         => [function (Client $c) { return $c->invoices->all('u'); }, 'GET', '/invoice', ['uuid' => 'u']],
            'invoices.find'        => [function (Client $c) { return $c->invoices->find('u', 2); }, 'GET', '/invoice/single', ['invoice_id' => '2']],
            'invoices.pay'         => [function (Client $c) { return $c->invoices->pay('u', 'INV-1'); }, 'POST', '/invoice/pay', ['invoice_number' => 'INV-1']],
            'invoices.cancel'      => [function (Client $c) { return $c->invoices->cancel('u', 2); }, 'POST', '/invoice/cancel', ['invoice_id' => 2]],

            'links.create'         => [function (Client $c) { return $c->paymentLinks->create(['uuid' => 'u', 'name' => 'n', 'description' => 'd']); }, 'POST', '/payme', ['type' => 'onetime']],
            'links.all'            => [function (Client $c) { return $c->paymentLinks->all('u'); }, 'GET', '/payme', ['uuid' => 'u']],
            'links.find'           => [function (Client $c) { return $c->paymentLinks->find('u', 'shoes'); }, 'GET', '/payme/single', ['link_slug' => 'shoes']],
            'links.slug'           => [function (Client $c) { return $c->paymentLinks->isSlugAvailable('shoes'); }, 'POST', '/payme/slug', ['link_slug' => 'shoes']],
            'links.goLive'         => [function (Client $c) { return $c->paymentLinks->goLive('u', 12); }, 'POST', '/payme/live', ['payme_id' => 12]],
            'links.tip'            => [function (Client $c) { return $c->paymentLinks->toggleTip('u', 'shoes'); }, 'POST', '/payme/tip', ['link_slug' => 'shoes']],
            'links.payments'       => [function (Client $c) { return $c->paymentLinks->payments('12'); }, 'GET', '/payme/payments', ['link_id' => '12']],
            'links.initiate'       => [function (Client $c) { return $c->paymentLinks->initiatePayment(['link_id' => 'shoes']); }, 'POST', '/payme/initialize', ['link_id' => 'shoes']],
            'links.complete'       => [function (Client $c) { return $c->paymentLinks->completePayment('u', 'shoes', 5, '1234'); }, 'POST', '/payme/complete', ['slug' => 'shoes', 'payment_id' => 5, 'otp' => '1234']],
            'links.addMargin'      => [function (Client $c) { return $c->paymentLinks->addMargin('u', 'shoes', 5, 500); }, 'POST', '/payme/add-margin', ['margin_amount' => 500]],

            'kyc.bvn'              => [function (Client $c) { return $c->kyc->bvn('22222222222'); }, 'GET', '/kyc/ng/lookup/bvn', ['bvn' => '22222222222']],
            'kyc.nin'              => [function (Client $c) { return $c->kyc->nin('1'); }, 'GET', '/kyc/ng/lookup/nin', ['nin' => '1']],
            'kyc.phone'            => [function (Client $c) { return $c->kyc->phone('080'); }, 'GET', '/kyc/ng/lookup/phone', ['phone_number' => '080']],
            'kyc.nuban'            => [function (Client $c) { return $c->kyc->nuban('012', '058'); }, 'GET', '/kyc/ng/lookup/nuban', ['account_number' => '012', 'bank_code' => '058']],
            'kyc.dl'               => [function (Client $c) { return $c->kyc->driversLicense('DL1'); }, 'GET', '/kyc/ng/lookup/dl', ['license_number' => 'DL1']],
            'kyc.tin'              => [function (Client $c) { return $c->kyc->tin('T1'); }, 'GET', '/kyc/ng/lookup/tin', ['tin' => 'T1']],
            'kyc.cac'              => [function (Client $c) { return $c->kyc->cac('RC1', 'COMPANY'); }, 'GET', '/kyc/ng/lookup/cac/rc', ['rc_number' => 'RC1', 'company_type' => 'COMPANY']],
            'kyc.searchCac'        => [function (Client $c) { return $c->kyc->searchCac('Pandascrow'); }, 'GET', '/kyc/ng/lookup/cac/name', ['searchTerm' => 'Pandascrow']],
            'kyc.email'            => [function (Client $c) { return $c->kyc->email('a@b.c'); }, 'GET', '/kyc/lookup/email', ['email' => 'a@b.c']],
        ];
    }

    /**
     * @dataProvider calls
     * @param array<string, mixed> $expected
     */
    #[DataProvider('calls')]
    public function testRoute(callable $call, string $method, string $path, array $expected): void
    {
        $call(self::$ps);
        $r = self::$http->last();

        $this->assertSame($method, $r['method']);
        $this->assertSame($path, $r['path']);

        $sent = $method === 'GET' ? $r['query'] : ($r['json'] ?? []);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $sent, "{$path} should send {$key}");
            $this->assertEquals($value, $sent[$key], "{$path} {$key}");
        }
    }

    public function testTransferGeneratesShortIdempotencyKey(): void
    {
        self::$ps->bank->transfer('u', 100, ['account_number' => '1', 'account_name' => 'A', 'bank_code' => '058']);
        $key = self::$http->last()['json']['idempotency_key'];
        // the transfer provider rejects references longer than 25 characters
        $this->assertLessThanOrEqual(25, strlen($key));
    }

    public function testTransferValidatesBank(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::$ps->bank->transfer('u', 100, ['account_number' => '1']);
    }
}
