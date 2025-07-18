<?php

declare(strict_types=1);

namespace CodebarAg\MFiles\DTO;

use CodebarAg\MFiles\DTO\User\ServerVaultCapabilities;
use Illuminate\Support\Arr;

final class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?bool $enabled,
        public readonly ?bool $isAdminUser,
        public readonly ?string $accountName,
        public readonly ?string $loginHint,
        public readonly ?int $aclMode,
        public readonly ?int $authenticationType,
        public readonly ?bool $canCreateObjects,
        public readonly ?bool $canForceUndoCheckout,
        public readonly ?bool $canManageCommonUiSettings,
        public readonly ?bool $canManageTraditionalFolders,
        public readonly ?bool $canManageCommonViews,
        public readonly ?bool $canMaterializeViews,
        public readonly ?bool $canSeeAllObjects,
        public readonly ?bool $canSeeDeletedObjects,
        public readonly ?bool $internalUser,
        public readonly ?bool $licenseAllowsModifications,
        public readonly ?bool $hasFullControlOfVault,
        public readonly ?bool $isReadOnlyLicense,
        public readonly ?string $serialNumber,
        public readonly ?string $deployment,
        public readonly ?string $licenseString,
        public readonly ?int $licenseType,
        public readonly ?bool $automaticMetadataEnabled,
        public readonly ?bool $canDestroyObjects,
        public readonly ?ServerVaultCapabilities $serverVaultCapabilities,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::get($data, 'UserID'),
            name: Arr::get($data, 'FullName'),
            email: Arr::get($data, 'AccountName'),
            enabled: Arr::get($data, 'enabled'),
            isAdminUser: Arr::get($data, 'IsAdminUser'),
            accountName: Arr::get($data, 'AccountName'),
            loginHint: Arr::get($data, 'LoginHint'),
            aclMode: Arr::get($data, 'ACLMode'),
            authenticationType: Arr::get($data, 'AuthenticationType'),
            canCreateObjects: Arr::get($data, 'CanCreateobjects'),
            canForceUndoCheckout: Arr::get($data, 'CanForceUndoCheckout'),
            canManageCommonUiSettings: Arr::get($data, 'CanManageCommonUISettings'),
            canManageTraditionalFolders: Arr::get($data, 'CanManageTraditionalFolders'),
            canManageCommonViews: Arr::get($data, 'CanManageCommonViews'),
            canMaterializeViews: Arr::get($data, 'CanMaterializeViews'),
            canSeeAllObjects: Arr::get($data, 'CanSeeAllObjects'),
            canSeeDeletedObjects: Arr::get($data, 'CanSeeDeletedObjects'),
            internalUser: Arr::get($data, 'InternalUser'),
            licenseAllowsModifications: Arr::get($data, 'LicenseAllowsModifications'),
            hasFullControlOfVault: Arr::get($data, 'HasFullControlOfVault'),
            isReadOnlyLicense: Arr::get($data, 'isReadOnlyLicense'),
            serialNumber: Arr::get($data, 'SerialNumber'),
            deployment: Arr::get($data, 'Deployment'),
            licenseString: Arr::get($data, 'licenseString'),
            licenseType: Arr::get($data, 'licenseType'),
            automaticMetadataEnabled: Arr::get($data, 'AutomaticMetadataEnabled'),
            canDestroyObjects: Arr::get($data, 'CanDestroyObjects'),
            serverVaultCapabilities: Arr::has($data, 'ServerVaultCapabilities') ? ServerVaultCapabilities::fromArray(Arr::get($data, 'ServerVaultCapabilities', [])) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'enabled' => $this->enabled,
            'isAdminUser' => $this->isAdminUser,
            'accountName' => $this->accountName,
            'loginHint' => $this->loginHint,
            'aclMode' => $this->aclMode,
            'authenticationType' => $this->authenticationType,
            'canCreateObjects' => $this->canCreateObjects,
            'canForceUndoCheckout' => $this->canForceUndoCheckout,
            'canManageCommonUiSettings' => $this->canManageCommonUiSettings,
            'canManageTraditionalFolders' => $this->canManageTraditionalFolders,
            'canManageCommonViews' => $this->canManageCommonViews,
            'canMaterializeViews' => $this->canMaterializeViews,
            'canSeeAllObjects' => $this->canSeeAllObjects,
            'canSeeDeletedObjects' => $this->canSeeDeletedObjects,
            'internalUser' => $this->internalUser,
            'licenseAllowsModifications' => $this->licenseAllowsModifications,
            'hasFullControlOfVault' => $this->hasFullControlOfVault,
            'isReadOnlyLicense' => $this->isReadOnlyLicense,
            'serialNumber' => $this->serialNumber,
            'deployment' => $this->deployment,
            'licenseString' => $this->licenseString,
            'licenseType' => $this->licenseType,
            'automaticMetadataEnabled' => $this->automaticMetadataEnabled,
            'canDestroyObjects' => $this->canDestroyObjects,
            'serverVaultCapabilities' => $this->serverVaultCapabilities?->toArray(),
        ];
    }
}
