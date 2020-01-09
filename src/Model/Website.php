<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright 2019 Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

declare(strict_types=1);

namespace Shopgate\WebsiteSampleData\Model;

use Exception;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\WebsiteFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Website
{
    /** @var FixtureManager */
    protected $fixtureManager;
    /** @var Csv */
    protected $csvReader;
    /** @var StoreManagerInterface */
    protected $storeManager;
    /** @var WebsiteRepositoryInterface */
    private $websiteRepository;
    /** @var GroupFactory */
    private $groupFactory;
    /** @var GroupResource */
    private $groupResource;
    /** @var GroupRepositoryInterface */
    private $groupRepository;
    /** @var WebsiteResource */
    private $webisteResource;
    /** @var WebsiteFactory */
    private $websiteFactory;
    /** @var StoreRepositoryInterface */
    private $storeRepository;
    /** @var StoreFactory */
    private $storeFactory;
    /** @var StoreResource */
    private $storeResource;
    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @param SampleDataContext          $sampleDataContext
     * @param StoreManagerInterface      $storeManager
     * @param WebsiteFactory             $websiteFactory
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param WebsiteResource            $websiteResource
     * @param GroupFactory               $groupFactory
     * @param GroupResource              $groupResource
     * @param GroupRepositoryInterface   $groupRepository
     * @param StoreFactory               $storeFactory
     * @param StoreRepositoryInterface   $storeRepository
     * @param StoreResource              $storeResource
     * @param WriterInterface            $writer
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        StoreManagerInterface $storeManager,
        WebsiteFactory $websiteFactory,
        WebsiteRepositoryInterface $websiteRepository,
        WebsiteResource $websiteResource,
        GroupFactory $groupFactory,
        GroupResource $groupResource,
        GroupRepositoryInterface $groupRepository,
        StoreFactory $storeFactory,
        StoreRepositoryInterface $storeRepository,
        StoreResource $storeResource,
        WriterInterface $writer
    ) {
        $this->fixtureManager    = $sampleDataContext->getFixtureManager();
        $this->csvReader         = $sampleDataContext->getCsvReader();
        $this->storeManager      = $storeManager;
        $this->websiteRepository = $websiteRepository;
        $this->webisteResource   = $websiteResource;
        $this->websiteFactory    = $websiteFactory;
        $this->groupFactory      = $groupFactory;
        $this->groupResource     = $groupResource;
        $this->groupRepository   = $groupRepository;
        $this->storeFactory      = $storeFactory;
        $this->storeRepository   = $storeRepository;
        $this->storeResource     = $storeResource;
        $this->writer            = $writer;
    }

    /**
     * @param string $websiteFixture
     * @param string $groupFixture
     * @param string $storeFixture
     * @param string $configFixture
     *
     * @throws LocalizedException
     */
    public function install(
        string $websiteFixture,
        string $groupFixture,
        string $storeFixture,
        string $configFixture
    ): void {
        $websites = $this->runCreator(
            $websiteFixture,
            function (array $row) {
                return $this->createWebsite($row['code'], $row['name'], (int) $row['is_default']);
            }
        );

        $groups = $this->runCreator(
            $groupFixture,
            function (array $row) use ($websites) {
                return $this->createGroup($row['code'], $row['name'], $websites[$row['website_code']]);
            }
        );

        $stores = $this->runCreator(
            $storeFixture,
            function (array $row) use ($websites, $groups) {
                return $this->createStore(
                    $row['code'],
                    $row['name'],
                    $websites[$row['website_code']],
                    $groups[$row['group_code']],
                    (int) $row['order'],
                    (int) $row['active']
                );
            }
        );

        $this->runCreator(
            $configFixture,
            function (array $row) use ($stores) {
                $this->saveConfigs(
                    $row['locale'],
                    $row['weight_unit'],
                    $row['currency'],
                    (int) $stores[$row['code']]->getId()
                );
            }
        );

        $this->runCreator(
            $groupFixture,
            function (array $row) use ($groups, $stores) {
                $this->updateGroupStoreDefault($groups[$row['code']], (int) $stores[$row['store_default']]->getId());
            }
        );

        $this->runCreator(
            $websiteFixture,
            function (array $row) use ($websites, $groups) {
                $this->updateWebsiteGroupDefault(
                    $websites[$row['code']],
                    (int) $groups[$row['group_default']]->getId()
                );
            }
        );
    }

    /**
     * @param string   $fixture - csv file
     * @param callable $callable
     *
     * @return string[]
     * @throws LocalizedException
     */
    private function runCreator(string $fixture, callable $callable): array
    {
        $rows   = $this->getCsv($fixture);
        $header = array_shift($rows);
        $list   = [];
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }
            $row                = $data;
            $list[$row['code']] = $callable($row);
        }

        return $list;
    }

    /**
     * @param string $fileName
     *
     * @return string[]
     * @throws LocalizedException
     * @throws Exception
     */
    private function getCsv(string $fileName): array
    {
        $fileName = $this->fixtureManager->getFixture($fileName);

        return $this->csvReader->getData($fileName);
    }

    /**
     * @param string           $code
     * @param string           $name
     * @param WebsiteInterface $website
     * @param GroupInterface   $group
     * @param int              $order
     * @param int              $active
     *
     * @return StoreInterface
     * @throws AlreadyExistsException
     */
    private function createStore(
        string $code,
        string $name,
        WebsiteInterface $website,
        GroupInterface $group,
        int $order,
        int $active
    ): StoreInterface {

        try {
            $store = $this->storeRepository->get($code);
        } catch (NoSuchEntityException $e) {
            $store = $this->storeFactory->create();
            $store->addData(
                [
                    'code'       => $code,
                    'name'       => $name,
                    'website_id' => $website->getId(),
                    'group_id'   => $group->getId(),
                    'sort_order' => $order,
                    'is_active'  => $active
                ]
            );
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->storeResource->save($store);
        }

        return $store;
    }

    /**
     * @param string $code
     * @param string $name
     * @param int    $isDefault
     *
     * @return WebsiteInterface
     * @throws AlreadyExistsException
     */
    private function createWebsite(string $code, string $name, int $isDefault): WebsiteInterface
    {
        try {
            $website = $this->websiteRepository->get($code);
        } catch (NoSuchEntityException $e) {
            $website = $this->websiteFactory->create();
            $website->addData(
                [
                    'code'       => $code,
                    'name'       => $name,
                    'is_default' => $isDefault
                ]
            );
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->webisteResource->save($website);
        }

        return $website;
    }

    /**
     * @param string           $code
     * @param string           $name
     * @param WebsiteInterface $website
     *
     * @return GroupInterface
     * @throws Exception
     */
    private function createGroup(string $code, string $name, WebsiteInterface $website): GroupInterface
    {
        $groups = array_filter(
            $this->groupRepository->getList(),
            static function ($group) use ($code) {
                /** @var GroupInterface $group */
                return $group->getCode() === $code;
            }
        );
        $group  = array_pop($groups);
        if (!$group) {
            $group = $this->groupFactory->create();
            $group->addData(
                ['code' => $code, 'name' => $name, 'website_id' => $website->getId(), 'root_category_id' => 2]
            );
            $this->groupResource->save($group);
        }

        return $group;
    }

    /**
     * @param GroupInterface $group
     * @param int            $defaultStoreId
     *
     * @throws AlreadyExistsException
     */
    private function updateGroupStoreDefault(GroupInterface $group, int $defaultStoreId): void
    {
        /** @noinspection PhpParamsInspection */
        $this->groupResource->save($group->setDefaultStoreId($defaultStoreId));
    }

    /**
     * @param WebsiteInterface $website
     * @param int              $defaultGroupId
     *
     * @throws AlreadyExistsException
     */
    private function updateWebsiteGroupDefault(WebsiteInterface $website, int $defaultGroupId): void
    {
        /** @noinspection PhpParamsInspection */
        $this->webisteResource->save($website->setDefaultGroupId($defaultGroupId));
    }

    /**
     * @param string $locale   - en_US, de_DE, ru_RU, etc.
     * @param string $weight   - kgs, lbs
     * @param string $currency - USD, EUR, RUB, YEN, etc.
     * @param int    $id       - id of store, website or 0 for default
     * @param string $scope    - default, websites, stores
     */
    private function saveConfigs(
        string $locale,
        string $weight,
        string $currency,
        int $id = 1,
        string $scope = ScopeInterface::SCOPE_STORES
    ): void {
        $this->writer->save(DirectoryHelper::XML_PATH_DEFAULT_LOCALE, $locale, $scope, $id);
        $this->writer->save(DirectoryHelper::XML_PATH_WEIGHT_UNIT, $weight, $scope, $id);
        $this->writer->save(Currency::XML_PATH_CURRENCY_BASE, $currency, $scope, $id);
        $this->writer->save(Currency::XML_PATH_CURRENCY_DEFAULT, $currency, $scope, $id);
    }
}
