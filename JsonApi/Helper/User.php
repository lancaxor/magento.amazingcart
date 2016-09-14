<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 07.09.16
 * Time: 13:38
 */

namespace Amazingcard\JsonApi\Helper;


use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception;
use Magento\Framework\App\Action\Context;

class User
{
    /**
     * @var AccountManagementInterface
     */
    private $_accountManagement;

    /**
     * @var Session
     */
    private $_session;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var CustomerInterface
     */
    private $_customerObject;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterface
     */
    private $_defaultBilling;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterface
     */
    private $_defaultShipping;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface\
     */
    private $_customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $_addressRepository;

    public function __construct(
        AccountManagementInterface $customerAccountManagement,
        CustomerInterface   $customer,
        CustomerRepositoryInterface $repositoryInterface,
        Session $customerSession,
        AddressRepositoryInterface  $addressRepository
    ) {
        $this->_accountManagement = $customerAccountManagement;
        $this->_session = $customerSession;
        $this->customerData = $customer;
        $this->_customerRepository = $repositoryInterface;
        $this->_addressRepository = $addressRepository;
    }

    protected function _getError($message, $status = -1) {
        return [
            'error' => $status, // because there is ~stup~ bad idea to use the same status
                                // for logout success and changeInfo error (in wordpress sample)
            'status' => $status,
            'reason' => $message,
            'data'   => null
        ];
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * Login user by username/password.
     * Good idea is check if user already has logged in,
     * but no same check in wordpress, so I won't create smth cool like this
     * @param $username
     * @param $password
     * @return array
     * @see \Magento\Customer\Controller\Account\LoginPost
     */
    public function login($username, $password) {

        if(empty($username) || empty($password)) {
            return $this->_getError('Username and password fields required');
        }

        try {
            $this->_customerObject = $this->_accountManagement->authenticate($username, $password);
            $this->_session->setCustomerDataAsLoggedIn($this->_customerObject);
            $this->_session->regenerateId();

            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
        } catch (Exception\AuthenticationException $e) {

            return $this->_getError('Username/email/password is wrong');    // btw, $e->getMessage() is much better. Just sayin'

        } catch (Exception\LocalizedException $e) {
            return $this->_getError('Localized exception: ' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->_getError('Unknown exception: ' . $e->getMessage());
        }

        $this->_defaultBilling = $this->_addressRepository->getById($this->_customerObject->getDefaultBilling());
        $this->_defaultShipping = $this->_addressRepository->getById($this->_customerObject->getDefaultShipping());

        return [
            'status'    => 0,
            'reason'    => 'Successful Log',
            'data'      => [
                'customer'  => $this->_customerObject,
                'billing'   => $this->_defaultBilling,
                'shipping'  => $this->_defaultShipping,
                'additionalInfo'    => []   // for future
            ]
        ];
    }

    /**
     * Logout user
     * @see \Magento\Customer\Controller\Account\Logout
     */
    public function logout() {
        $this->_session->logout();

        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }

        $this->_customerObject = null;
        $this->_defaultBilling = null;
        $this->_defaultShipping = null;

        return [
            'status'    => 1,
            'reason'      => 'Successful'
        ];
    }

    public function updateProfile($firstName = null, $lastName = null, $email = null) {

        // checkers
        if(!$this->_session->isLoggedIn()) {
            return $this->_getError('Not Authorized', 1);
        }

        if(!isset($firstName) && !isset($lastName) && !isset($email)) {
            return $this->_getError('No Input');
        }

        // loading data about current user
        $customerId = $this->_session->getCustomer()->getId();
        $this->_customerObject = $this->_customerRepository->getById($customerId);

        // edit data
        if(isset($firstName)) {
            $this->_customerObject->setFirstname($firstName);
        }
        if(isset($lastName)) {
            $this->_customerObject->setLastname($lastName);
        }
        if(isset($email)) {
            $this->_customerObject->setEmail($email);
        }

        // to database
        try {
            $this->_customerRepository->save($this->_customerObject);
        } catch (\Exception $ex) {
            return $this->_getError('Error during saving data to DB: ' . $ex->getMessage());
        }

        $this->_defaultBilling = $this->_addressRepository->getById($this->_customerObject->getDefaultBilling());
        $this->_defaultShipping = $this->_addressRepository->getById($this->_customerObject->getDefaultShipping());

        return [
            'status'    => 0,
            'reason'    => 'Succesful updated profile', // btw, 'SuccessfulLY' is correct -_-
            'data'      => [
                'customer'  => $this->_customerObject,
                'billing'   => $this->_defaultBilling,
                'shipping'   => $this->_defaultShipping
            ]
        ];
    }

    /**
     * @param $userName
     * @param $password
     * @param $billingData
     * @return array
     */
    public function updateBilling($userName, $password, $billingData) {

        if(!isset($userName) || !isset($password)) {
            return $this->_getError('No input');
        }

        $loginInfo = $this->login($userName, $password);
        if(isset($loginInfo['error'])) {    // cannot authorize
            return $loginInfo;
        }

        $isEditedBilling = false;

        if(isset($billingData['billing_first_name'])) {
            $this->_defaultBilling->setFirstname($billingData['billing_first_name']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_last_name'])) {
            $this->_defaultBilling->setLastname($billingData['billing_last_name']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_company'])) {
            $this->_defaultBilling->setCompany($billingData['billing_company']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_address_1'])) {
            $this->_defaultBilling->setStreet($billingData['billing_address_1']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_address_2'])) {
            // but in Magento address is other billingAddress entity,
            // so we can create other address entity in database and set it as default...
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_city'])) {
            $this->_defaultBilling->setCity($billingData['billing_city']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_postcode'])) {
            $this->_defaultBilling->setPostcode($billingData['billing_postcode']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_state'])) {
            $this->_defaultBilling->setRegionId($billingData['billing_state']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_country'])) {
            $this->_defaultBilling->setCountryId($billingData['billing_country']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_phone'])) {
            $this->_defaultBilling->setTelephone($billingData['billing_phone']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_email'])) {
            // no email in billing, use customer email?..
            $isEditedBilling = true;
        }

        // save to database
        // and we don`t have to write smth if nothing was changed
        if($isEditedBilling) {
            $this->_addressRepository->save($this->_defaultBilling);
        }

        return [
            'status'    => 0,
            'reason'    => 'Succesfull updated billing',
            'data'      => [
                'customer'  => $this->_customerObject,
                'billing'   => $this->_defaultBilling,
                'shipping'  => $this->_defaultShipping
            ]
        ];
    }

    public function updateShipping($userName, $password, $shippingData) {

        if(!isset($userName) || !isset($password)) {
            return $this->_getError('No input');
        }

        $loginInfo = $this->login($userName, $password);
        if(isset($loginInfo['error'])) {    // cannot authorize
            return $loginInfo;
        }

        $isEditedShipping = false;
        if(isset($shippingData['shipping_first_name'])) {
            $this->_defaultShipping->setFirstname($shippingData['shipping_first_name']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_last_name'])) {
            $this->_defaultShipping->setLastname($shippingData['shipping_last_name']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_company'])) {
            $this->_defaultShipping->setCompany($shippingData['shipping_company']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_address_1'])) {
            $this->_defaultShipping->setStreet($shippingData['shipping_address_1']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_address_2'])) {

            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_city'])) {
            $this->_defaultShipping->setCity($shippingData['shipping_city']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_postcode'])) {
            $this->_defaultShipping->setPostcode($shippingData['shipping_postcode']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_state'])) {
            $this->_defaultShipping->setRegionId($shippingData['shipping_state']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_country'])) {
            $this->_defaultShipping->setCountryId($shippingData['shipping_country']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_phone'])) {
            $this->_defaultShipping->setTelephone($shippingData['shipping_phone']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_email'])) {
            $isEditedShipping = true;
        }

        // save to database
        if($isEditedShipping) {
            $this->_addressRepository->save($this->_defaultShipping);
        }

        return [
            'status'    => 0,
            'reason'    => 'Succesfull updated shipping',
            'data'      => [
                'customer'  => $this->_customerObject,
                'billing'   => $this->_defaultBilling,
                'shipping'  => $this->_defaultShipping
            ]
        ];
    }

    public function changePassword($userName, $currentPassword, $newPassword) {
        $loginInfo = $this->login($userName, $currentPassword);

        if(isset($loginInfo['error'])) {
            return $loginInfo;
        }

        try {
            $success = $this->_accountManagement->changePassword($userName, $currentPassword, $newPassword);
        } catch(Exception\LocalizedException $e) {
            return $this->_getError($e->getMessage());
        }

        if(isset($success) && $success) {
            return [
                'status'    => 0,
                'reason'    => 'Password Updated'
            ];
        }
        return [];
    }

    /**
     * @param $customerId integer
     * @return CustomerInterface
     */
    public function getUserById($customerId) {
        return $this->_customerRepository->getById($customerId);
    }
}