<?php

namespace Comba\Bundle\Modx;

use DocumentParser;

class ModxCart extends ModxOptions
{

    private ModxUser $_user;

    public function __construct(?object $parent = null, ?DocumentParser $modx = null)
    {
        parent::__construct($parent, $modx);

        $this->_user = new ModxUser($this);

        $this->isCachable = true;
        $this->cacheLifetime = 3600;
    }

    public function get(): ?array
    {
        return $this->read();
    }

    private function read(): ?array
    {
        if (empty($this->getID())) {
            return null;
        }

        $doc = [];
        $ret = json_decode(
            $this->request('DocumentRead', [
                    'Document' => [
                        'uid' => $this->getID()
                    ]
                ]
            ),
            true);

        if ($ret['result'] == 'ok') {

            $doc = $ret['Document'];

            if (!empty($doc['specs'])) {
                foreach ($doc['specs'] as $k => $spec) {
                    if ($spec['docspec_enable'] == 0) {
                        unset($doc['spec'][$k]);
                        continue;
                    }
                    if ($spec['docspec_price'] <= 0) {
                        $doc['specs'][$k]['bgclass'] = 'text-secondary';
                    }
                }
            }
        }

        return $doc;
    }

    /**
     * Return Document UID
     *
     * @return string|null
     */
    public function getID(): ?string
    {
        if (!empty($this->getOptions('id'))) {
            return $this->getOptions('id');
        }

        if (!$this->isBot()) {
            $this->setOptions('id', $this->prepareDocumentId());
        } else {
            $this->setOptions('isBot', true);
        }

        return $this->getOptions('id');
    }

    /**
     * Return current Document ID from database by session
     * return null for empty session
     *
     * @return string|null
     */
    public function prepareDocumentId(): ?string
    {
        $session = $this->_user->getSession();
        if (empty($session)) {
            return null;
        }

        $data = json_decode(
            $this->request('DocumentGetCurrentId', [
                    'Document' => [
                        'session' => $session
                    ]
                ]
            ),
            true);

        $uid = !empty($data['Document']['uid']) ? $data['Document']['uid'] : null;
        $this->log('DEBUG', 'ID is ' . $uid);

        if (empty($uid)) {
            $this->invalidateCache($session);
        }

        return $uid;
    }

    public function setID(string $uid): ModxCart
    {
        $this->setOptions('id', $uid);
        return $this;
    }

    /**
     * Insert product into cart
     * @return array|null
     */
    public function insert(): ?array
    {
        if (empty($this->getID())) {
            $this->log('CRITICAL', 'insert() document ID empty');
            return null;
        }

        $ret = json_decode($this->request(
            'DocumentSpecInsert', [
                'Document' => [
                    'uid' => $this->getID(),
                    'amount' => $this->getOptions('amount'),
                    'useruid' => $this->_user->getId(),
                ],
                'Product' => $this->getOptions('Product'),
            ]
        ), true);

        if (!empty($ret)) {
            $this->invalidateCache($this->getID());
        }
        return $ret;
    }

    /**
     * Update amount product in cart
     * @return array|null
     */
    public function update(): ?array
    {
        if (empty($this->getID())) {
            $this->log('CRITICAL', 'update() document ID empty');
            return null;
        }

        $spec = array(
            'uid' => $this->getID(),
            'specid' => $this->getOptions('specid'),
            'amount' => (int)$this->getOptions('amount'),
            'useruid' => $this->_user->getId(),
        );

        if (empty($spec['uid']) || empty($spec['specid'])) {
            $this->log('CRITICAL', 'update() spec ID empty');
            return null;
        }

        $ret = json_decode(
            $this->request(
                'DocumentSpecUpdate', [
                    'Document' => $spec
                ]
            ),
            true);

        if (!empty($ret)) {
            $this->invalidateCache($this->getID());
        }

        return $ret;
    }

    /**
     * Delete product from cart
     * @return array|null
     */
    public function delete(): ?array
    {
        if (empty($this->getID())) {
            $this->log('CRITICAL', 'delete() document ID empty');
            return null;
        }

        $spec = array(
            'uid' => $this->getID(),
            'specid' => $this->getOptions('specid'),
            'useruid' => $this->_user->getId(),
        );

        if (empty($spec['specid']) || empty($spec['uid']) || empty($spec['useruid'])) {
            $this->log('CRITICAL', 'delete() specid, userid empty');
            return null;
        }

        $ret = json_decode(
            $this->request(
                'DocumentSpecDelete', [
                    'Document' => $spec
                ]
            ),
            true);

        if (!empty($ret)) {
            $this->invalidateCache($this->getID());
        }

        return $ret;
    }

    /**
     * Сповістити про оплату замовлення
     * @param array $info
     * @return array|null
     */
    public function notifyPayment(array $info): ?array
    {
        if (empty($this->getID())) {
            $this->log('CRITICAL', 'nofifyPayment() document ID empty');
            return null;
        }

        $ret = json_decode($this->request(
            'PaymentNotify', [
                'Document' => [
                    'uid' => $this->getID(),
                    'useruid' => $this->_user->getId(),
                    'payment_info' => $info,
                ],
            ]
        ), true);

        if (!empty($ret)) {
            $this->invalidateCache($this->getID());
        }
        return $ret;
    }
}
