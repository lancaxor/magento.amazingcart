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
    private $accountManagement;

    /**
     * @var Session
     */
    private $session;

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
    private $customerObject;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterface
     */
    private $defaultBilling;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterface
     */
    private $defaultShipping;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface\
     */
    private $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    public function __construct(
        AccountManagementInterface $customerAccountManagement,
        CustomerInterface   $customer,
        CustomerRepositoryInterface $repositoryInterface,
        Session $customerSession,
        AddressRepositoryInterface  $addressRepository
    ) {
        $this->accountManagement = $customerAccountManagement;
        $this->session = $customerSession;
        $this->customerObject = $customer;
        $this->customerRepository = $repositoryInterface;
        $this->addressRepository = $addressRepository;
    }

    protected function getError($message, $status = -1) {
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
            return $this->getError('Username and password fields required');
        }

        try {
            $this->customerObject = $this->accountManagement->authenticate($username, $password);
            $this->session->setCustomerDataAsLoggedIn($this->customerObject);
            $this->session->regenerateId();

            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
        } catch (Exception\AuthenticationException $e) {

            return $this->getError('Username/email/password is wrong');    // btw, $e->getMessage() is much better. Just sayin'

        } catch (Exception\LocalizedException $e) {
            return $this->getError('Localized exception: ' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->getError('Unknown exception: ' . $e->getMessage());
        }

        $defaultBilling = $this->customerObject->getDefaultBilling();
        $defaultShipping = $this->customerObject->getDefaultShipping();

        if(isset($defaultBilling)) {
            $this->defaultBilling = $this->addressRepository->getById($defaultBilling);
        } else {
            $this->defaultBilling = [];
        }

        if(isset($defaultShipping)) {
            $this->defaultShipping = $this->addressRepository->getById($defaultShipping);
        } else {
            $this->defaultShipping = [];
        }

        return [
            'status'    => 0,
            'reason'    => 'Successful Log',
            'data'      => [
                'customer'  => $this->customerObject,
                'billing'   => $this->defaultBilling,
                'shipping'  => $this->defaultShipping,
                'additionalInfo'    => []   // for future
            ]
        ];
    }

    /**
     * @return bool|array
     */
    public function getCurrentUser() {
        $isLoggedIn = $this->session->isLoggedIn();
        if($isLoggedIn) {
            return [
                'isLoggedIn'    => $isLoggedIn,
                'customerId'    => $this->session->getCustomerId(),
                'customerEmail' => $this->session->getCustomer()->getEmail(),
                'customerName'  => $this->session->getCustomer()->getName()
            ];
        }
        return $isLoggedIn;
    }

    /**
     * Logout user
     * @see \Magento\Customer\Controller\Account\Logout
     */
    public function logout() {
        $this->session->logout();

        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }

        $this->customerObject = null;
        $this->defaultBilling = null;
        $this->defaultShipping = null;

        return [
            'status'    => 1,
            'reason'      => 'Successful'
        ];
    }

    public function updateProfile($firstName = null, $lastName = null, $email = null) {

        // checkers
        if(!$this->session->isLoggedIn()) {
            return $this->getError('Not Authorized', 1);
        }

        if(!isset($firstName) && !isset($lastName) && !isset($email)) {
            return $this->getError('No Input');
        }

        // loading data about current user
        $customerId = $this->session->getCustomer()->getId();
        $this->customerObject = $this->customerRepository->getById($customerId);

        // edit data
        if(isset($firstName)) {
            $this->customerObject->setFirstname($firstName);
        }
        if(isset($lastName)) {
            $this->customerObject->setLastname($lastName);
        }
        if(isset($email)) {
            $this->customerObject->setEmail($email);
        }

        // to database
        try {
            $this->customerRepository->save($this->customerObject);
        } catch (\Exception $ex) {
            return $this->getError('Error during saving data to DB: ' . $ex->getMessage());
        }

        $defaultBilling = $this->customerObject->getDefaultBilling();
        $defaultShipping = $this->customerObject->getDefaultShipping();

        if(isset($defaultBilling)) {
            $this->defaultBilling = $this->addressRepository->getById($defaultBilling);
        } else {
            $this->defaultBilling = [];
        }

        if(isset($defaultShipping)) {
            $this->defaultShipping = $this->addressRepository->getById($defaultShipping);
        } else {
            $this->defaultShipping = [];
        }


        return [
            'status'    => 0,
            'reason'    => 'Succesful updated profile', // btw, 'SuccessfulLY' is correct -_-
            'data'      => [
                'customer'  => $this->customerObject,
                'billing'   => $this->defaultBilling,
                'shipping'   => $this->defaultShipping
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
            return $this->getError('No input');
        }

        $loginInfo = $this->login($userName, $password);
        if(isset($loginInfo['error'])) {    // cannot authorize
            return $loginInfo;
        }

        $isEditedBilling = false;

        if(isset($billingData['billing_first_name'])) {
            $this->defaultBilling->setFirstname($billingData['billing_first_name']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_last_name'])) {
            $this->defaultBilling->setLastname($billingData['billing_last_name']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_company'])) {
            $this->defaultBilling->setCompany($billingData['billing_company']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_address_1'])) {
            $this->defaultBilling->setStreet($billingData['billing_address_1']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_address_2'])) {
            // but in Magento address is other billingAddress entity,
            // so we can create other address entity in database and set it as default...
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_city'])) {
            $this->defaultBilling->setCity($billingData['billing_city']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_postcode'])) {
            $this->defaultBilling->setPostcode($billingData['billing_postcode']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_state'])) {
            $this->defaultBilling->setRegionId($billingData['billing_state']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_country'])) {
            $this->defaultBilling->setCountryId($billingData['billing_country']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_phone'])) {
            $this->defaultBilling->setTelephone($billingData['billing_phone']);
            $isEditedBilling = true;
        }
        if(isset($billingData['billing_email'])) {
            // no email in billing, use     customer email?..
            $isEditedBilling = true;
        }

        // save to database
        // and we don`t have to write smth if nothing was changed
        if($isEditedBilling) {
            $this->addressRepository->save($this->defaultBilling);
        }

        return [
            'status'    => 0,
            'reason'    => 'Succesfull updated billing',
            'data'      => [
                'customer'  => $this->customerObject,
                'billing'   => $this->defaultBilling,
                'shipping'  => $this->defaultShipping
            ]
        ];
    }

    public function updateShipping($userName, $password, $shippingData) {

        if(!isset($userName) || !isset($password)) {
            return $this->getError('No input');
        }

        $loginInfo = $this->login($userName, $password);
        if(isset($loginInfo['error'])) {    // cannot authorize
            return $loginInfo;
        }

        $isEditedShipping = false;
        if(isset($shippingData['shipping_first_name'])) {
            $this->defaultShipping->setFirstname($shippingData['shipping_first_name']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_last_name'])) {
            $this->defaultShipping->setLastname($shippingData['shipping_last_name']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_company'])) {
            $this->defaultShipping->setCompany($shippingData['shipping_company']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_address_1'])) {
            $this->defaultShipping->setStreet($shippingData['shipping_address_1']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_address_2'])) {

            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_city'])) {
            $this->defaultShipping->setCity($shippingData['shipping_city']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_postcode'])) {
            $this->defaultShipping->setPostcode($shippingData['shipping_postcode']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_state'])) {
            $this->defaultShipping->setRegionId($shippingData['shipping_state']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_country'])) {
            $this->defaultShipping->setCountryId($shippingData['shipping_country']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_phone'])) {
            $this->defaultShipping->setTelephone($shippingData['shipping_phone']);
            $isEditedShipping = true;
        }
        if(isset($shippingData['shipping_email'])) {
            $isEditedShipping = true;
        }

        // save to database
        if($isEditedShipping) {
            $this->addressRepository->save($this->defaultShipping);
        }

        return [
            'status'    => 0,
            'reason'    => 'Succesfull updated shipping',
            'data'      => [
                'customer'  => $this->customerObject,
                'billing'   => $this->defaultBilling,
                'shipping'  => $this->defaultShipping
            ]
        ];
    }

    public function changePassword($userName, $currentPassword, $newPassword) {
        $loginInfo = $this->login($userName, $currentPassword);

        if(isset($loginInfo['error'])) {
            return $loginInfo;
        }

        try {
            $success = $this->accountManagement->changePassword($userName, $currentPassword, $newPassword);
        } catch(Exception\LocalizedException $e) {
            return $this->getError($e->getMessage());
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
        return $this->customerRepository->getById($customerId);
    }

    /**
     * @param $email    string
     * @param $password string
     * @param $firstName    string
     * @param $lastName string
     * @return array|CustomerInterface
     */
    public function registerUser($email, $password, $firstName, $lastName) {
        $userModel = $this->customerObject->setEmail($email)
            ->setFirstname($firstName)
            ->setLastname($lastName);

        try {
            return $this->accountManagement->createAccount($userModel, $password);
        } catch (Exception\LocalizedException $exception) {
            return $this->getError($exception->getMessage());
        }
    }
}