<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment\Lib;

use GuzzleHttp\Client;
use Plugin\JaccsPayment\Lib\Xml\XmlBasic;
use Plugin\JaccsPayment\Lib\Xml\Transaction;
use Plugin\JaccsPayment\Lib\Xml\Shippingrequest;
use Plugin\JaccsPayment\Lib\Xml\Modifytransaction;
use Plugin\JaccsPayment\Lib\Xml\Getauthori;

class HttpSend
{
    protected static $client;

    public function GetClient()
    {
        if (self::$client) {
            return self::$client;
        }

        self::$client = new Client([
            'base_uri' => Inc::baseuri,
            'timeout' => Inc::http_timeout,
        ]);

        return self::$client;
    }

    /**
     * @param XmlBasic $sendData
     *
     * @return null|Getauthori\Response|Modifytransaction\Response|Shippingrequest\Response|Transaction\Response
     *
     * @throws JaccsException
     */
    public function sendData(XmlBasic $sendData)
    {
        $id = (new \DateTime())->format(\DateTime::RFC3339).rand(0, 99999999);
        $logTag = ['send' => 1, 'jaccs-logid' => $id];

        if ($sendData instanceof Transaction\Request) {
            $uri = Inc::transactionUri;

            if ($sendData->getCustomer()) {
                $logTag['shop_order_id'] = $sendData->getCustomer()->getShopOrderId();
            }
        } elseif ($sendData instanceof Shippingrequest\Request) {
            $uri = Inc::shippingrequestUri;

            if ($sendData->getTransactionInfo()) {
                $logTag['transaction_id'] = $sendData->getTransactionInfo()->getTransactionId();
            }
        } elseif ($sendData instanceof Modifytransaction\Request) {
            $uri = Inc::modifytransactionUri;

            if ($sendData->getTransactionInfo()) {
                $logTag['transaction_id'] = $sendData->getTransactionInfo()->getTransactionId();
            }
        } elseif ($sendData instanceof Getauthori\Request) {
            $uri = Inc::getauthoriUri;

            if ($sendData->getTransactionInfo()) {
                $logTag['transaction_id'] = $sendData->getTransactionInfo()->getTransactionId();
            }
        } else {
            throw new JaccsException('通信タイプは存在しません。');
        }

        $body = $sendData->toXmlText();

        if ($sendData instanceof Transaction\Request || $sendData instanceof Modifytransaction\Request) {
            $logSendData = clone $sendData;

            if ($logSendData->getCustomer()) {
                $logSendData->getCustomer()->setName(substr($logSendData->getCustomer()->getName(), 0, -2).'****');
                $logSendData->getCustomer()->setZip(substr($logSendData->getCustomer()->getZip(), 0, -4).'****');
                $logSendData->getCustomer()->setAddress(substr($logSendData->getCustomer()->getAddress(), 0, -6).'****');
                $logSendData->getCustomer()->setTel(substr($logSendData->getCustomer()->getTel(), 0, -4).'****');
            }

            if ($logSendData->getShip()) {
                $logSendData->getShip()->setShipName(substr($logSendData->getShip()->getShipName(), 0, -2).'****');
                $logSendData->getShip()->setShipZip(substr($logSendData->getShip()->getShipZip(), 0, -4).'****');
                $logSendData->getShip()->setShipTel(substr($logSendData->getShip()->getShipTel(), 0, -4).'****');
                $logSendData->getShip()->setShipAddress(substr($logSendData->getShip()->getShipAddress(), 0, -4).'****');
            }


            log_info($logSendData->toXmlText(), $logTag);
        } else {
            log_info($body, $logTag);
        }

        $response = self::GetClient()->post($uri, ['body' => $body]);

        $logTag['send'] = 0;

        $xml = '';

        if ($response->getBody()) {
            $xml = $response->getBody()->getContents();
            log_info($xml, $logTag);
        } else {
            log_info('not response body', $logTag);
        }

        if ($response->getStatusCode() == 200) {
            $objResponse = null;
            if ($sendData instanceof Transaction\Request) {
                $objResponse = new Transaction\Response($xml);
                if ($objResponse->getTransactionInfo()) {
                    $logTag['transaction_id'] = $objResponse->getTransactionInfo()->getTransactionId();
                    log_info('new transaction', $logTag);
                }
            } elseif ($sendData instanceof Shippingrequest\Request) {
                $objResponse = new Shippingrequest\Response($xml);
            } elseif ($sendData instanceof Modifytransaction\Request) {
                $objResponse = new Modifytransaction\Response($xml);
            } elseif ($sendData instanceof Getauthori\Request) {
                $objResponse = new Getauthori\Response($xml);
            }

            return $objResponse;
        } else {
            $mess = sprintf('[http error]http code:%d', $response->getStatusCode());
            log_error(sprintf('[http error]http code:%d', $response->getStatusCode()), $logTag);
            throw new JaccsException($mess);
        }
    }
}
