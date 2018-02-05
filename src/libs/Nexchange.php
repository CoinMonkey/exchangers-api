<?php

namespace coinmonkey\exchangers\libs;

use coinmonkey\interfaces\InstantExchangerInterface;
use coinmonkey\interfaces\OrderInterfaceInterface;
use coinmonkey\interfaces\AmountInterface;
use coinmonkey\interfaces\CoinInterface;
use coinmonkey\interfaces\OrderInterface as OrderExchange;
use coinmonkey\entities\Amount;
use coinmonkey\exchangers\tools\Nexchange as NexchangeTool;

class Nexchange implements InstantExchangerInterface
{
    private $referral = '';
    private $tool;
    private $cache;

    const STRING_ID = 'nexchange';
    const EXCHANGER_TYPE = 'instant';

    public function __construct($referral, $cache = true)
    {
        $this->referral = $referral;
        $this->cache = $cache;
        $this->tool = new NexchangeTool($referral);
    }

    public function getId() : string
    {
        return self::STRIND_ID;
    }

    public function withdraw(string $address, AmountInterface $amount)
    {
        return null;
    }

    public function getExchangeStatus(OrderExchange $order) : ?int
    {
        $address = $order->getAddress();

        $nxOrder = $this->tool->getOrder($address->getExchangerOrderId());

        switch($nxOrder->status_name[0][0]) {
            case '11': return OrderExchange::STATUS_WAIT_CLIENT_TRANSACTION;
            case '12': return OrderExchange::STATUS_WAIT_EXCHANGER_PROCESSING;
            case '13': return OrderExchange::STATUS_EXCHANGER_PROCESSING;
            case '15': return OrderExchange::STATUS_DONE;
            default: return OrderExchange::STATUS_DONE;
        }

        return null;
    }

    public function getEstimateAmount(AmountInterface $amount, CoinInterface $coin2) : AmountInterface
    {
        $minimum = $this->tool->getMinimum($amount->getCoin()->getCode());
        $maximum = $this->tool->getMaximum($coin2->getCode());

        if($amount->getAmount() > $maximum | $amount->getAmount() < $minimum) {
            throw new \App\Exceptions\ErrorException('Minimum is ' . $minimum . ' ' . $amount->getCoin()->getCode() . ' and maximum is ' . $maximum . ' ' . $amount->getCoin()->getCode(), null, null, 0);
        }

        $cost = $this->tool->getPrice($amount->getCoin()->getCode(), $coin2->getCode(), $amount->getAmount());

        $cost = $cost-$this->tool->getWithdrawalFee($coin2->getCode());

        return new Amount(round($cost, 8, PHP_ROUND_HALF_UP), $coin2);
    }

    public function getMinAmount(CoinInterface $coin, CoinInterface $coin2) : ?int
    {
        return $this->tool->getMinimum($coin->getCode());
    }

    public function getMaxAmount(CoinInterface $coin, CoinInterface $coin2) : ?int
    {
        return $this->tool->getMaximum($coin->getCode());
    }

    public function makeDepositAddress(string $clientAddress, AmountInterface $amount, CoinInterface $coin2) : array
    {
        $res = $this->tool->createAnonymousOrder($amount->getCoin()->getCode(), $coin2->getCode(), $amount->getAmount(), $clientAddress);

        return [
            'private' => null,
            'public' => null,
            'address' => $res->deposit_address->address,
            'id' => $res->unique_reference,
        ];
    }

    public function getMinConfirmations(CoinInterface $coin)
    {
        return $this->tool->getMinConfirmations($coin->getCode());
    }
}