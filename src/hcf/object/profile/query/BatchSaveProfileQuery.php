<?php

declare(strict_types=1);

namespace hcf\object\profile\query;

use hcf\object\profile\ProfileData;
use hcf\thread\CommonThread;
use hcf\thread\LocalThreaded;
use hcf\thread\types\SQLDataSourceThread;
use hcf\thread\types\ThreadType;

final class BatchSaveProfileQuery implements LocalThreaded {

    /**
     * @param ProfileData[] $profilesData
     */
    public function __construct(
        private array $profilesData
    ) {}

    /**
     * @param ThreadType $threadType
     *
     * This function is executed on other Thread to prevent lag spike on Main thread
     */
    public function run(ThreadType $threadType): void {
        if (!$threadType instanceof SQLDataSourceThread || $threadType->id() !== $this->threadId()) return;

        $provider = $threadType->getResource();

        foreach ($this->profilesData as $profileData) {
            // TODO: Load the profile from the data storored
            $storedProfileData = LoadProfileQuery::fetch($profileData->getXuid(), $profileData->getName(), $provider);

            if ($storedProfileData === null) continue;

            $storedProfileData->setFactionId($profileData->getFactionId());
            $storedProfileData->setFactionRole($profileData->getFactionRole());

            if ($profileData->getDeaths() !== -1) $storedProfileData->setDeaths($profileData->getDeaths());
            if ($profileData->getKills() !== -1) $storedProfileData->setKills($profileData->getKills());
            if ($profileData->getBalance() !== -1) $storedProfileData->setBalance($profileData->getBalance());

            SaveProfileQuery::push($storedProfileData, $provider);
        }
    }

    /**
     * This function is executed on the Main Thread because need use some function of pmmp
     */
    public function onComplete(): void {
        // TODO: Implement onComplete() method.
    }

    public function threadId(): int {
        return CommonThread::SQL_DATA_SOURCE;
    }
}