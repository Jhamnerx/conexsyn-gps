<?php

namespace Tobuli\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tobuli\Entities\FcmConfiguration;

class FcmConfigurationService
{
    public const KEY_ACCESS_TOKEN_PREFIX = 'fcm-access-token-';

    private GoogleAuthClient $googleAuthClient;
    private ?FcmConfiguration $defaultConfig;
    private Collection $configs;

    public function __construct()
    {
        $this->googleAuthClient = new GoogleAuthClient();
    }

    public function hasProjectConfig(?string $projectId): bool
    {
        return $projectId && $this->getConfigs()->where('project_id', $projectId)->count();
    }

    public function getAccessToken(string $projectId): ?string
    {
        $config = json_decode($this->getConfigs()->firstWhere('project_id', $projectId)->config, true);

        return $this->googleAuthClient->getOAuth2Token('firebase.messaging', $config, $this->getCacheKey($projectId));
    }

    public function resetAccessToken(string $projectId): void
    {
        $this->googleAuthClient->removeToken($this->getCacheKey($projectId));
    }

    private function getCacheKey(string $projectId): string
    {
        return self::KEY_ACCESS_TOKEN_PREFIX . $projectId;
    }

    public function getDefaultProjectId(): ?string
    {
        return $this->getDefaultConfig()->project_id ?? null;
    }

    public function store(array $input): FcmConfiguration
    {
        $item = FcmConfiguration::firstOrNew(['id' => $input['id'] ?? null]);

        Validator::validate($input, [
            'title'         => ['required', Rule::unique(FcmConfiguration::class)->ignore($item)],
            'is_default'    => ['required', 'bool'],
            'config'        => ['required', 'json'],
        ]);

        $config = json_decode($input['config'], true);

        Validator::validate($config, [
            'type'                          => 'required',
            'project_id'                    => Rule::unique(FcmConfiguration::class)->ignore($item),
            'private_key_id'                => 'required',
            'private_key'                   => 'required',
            'client_email'                  => 'required',
            'client_id'                     => 'required',
            'auth_uri'                      => 'required',
            'token_uri'                     => 'required',
            'auth_provider_x509_cert_url'   => 'required',
            'client_x509_cert_url'          => 'required',
            'universe_domain'               => 'required',
        ]);

        beginTransaction();

        if ($input['is_default']) {
            FcmConfiguration::where('id', '!=', $item->id)->update(['is_default' => false]);

            $this->defaultConfig = $item;
        }

        $item->fill($input);
        $item->project_id = $config['project_id'];
        $item->save();

        commitTransaction();

        return $item;
    }

    public function setDefault(int $id): void
    {
        $item = FcmConfiguration::findOrFail($id);

        beginTransaction();

        FcmConfiguration::query()->update(['is_default' => false]);
        $item->update(['is_default' => true]);

        commitTransaction();

        $this->defaultConfig = $item;
    }

    public function delete(int $id): bool
    {
        $item = FcmConfiguration::findOrFail($id);

        return $item->delete();
    }

    private function getConfigs(): Collection
    {
        if ($this->areConfigsLoaded()) {
            return $this->configs;
        }

        $this->configs = FcmConfiguration::get();
        $this->defaultConfig = $this->configs->firstWhere('is_default', true);

        return $this->configs;
    }

    private function getDefaultConfig(): ?FcmConfiguration
    {
        if ($this->areConfigsLoaded()) {
            return $this->defaultConfig;
        }

        $this->getConfigs();

        return $this->defaultConfig;
    }

    private function areConfigsLoaded(): bool
    {
        return isset($this->configs);
    }
}