<?php namespace Tobuli\Helpers\Templates\Builders;

use Appearance;
use CustomFacades\Server;
use Formatter;
use Carbon\Carbon;
use Tobuli\Entities\EmailTemplate;
use Tobuli\Entities\SmsTemplate;

abstract class TemplateBuilder
{
    protected $user;

    abstract protected function variables($item);
    abstract protected function placeholders();

    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function buildTemplate($template, $data = null)
    {
        $variables = $this->variables($data);

        if ($template instanceof EmailTemplate)
            $variables = array_merge($variables, $this->_variablesEmail());

        if ($template instanceof SmsTemplate)
            $variables = array_merge($variables, $this->_variablesSMS());


        $result = [
            'subject' => strtr($template->title, $variables),
            'body'    => strtr($template->note, $variables),
        ];

        array_walk($result, function (&$text) {
            $text = $this->replaceTransTags($text);
        });

        return $result;
    }

    public function getPlaceholders($template)
    {
        $placeholders = $this->placeholders();

        if ($template instanceof EmailTemplate)
            $placeholders =  array_merge($placeholders, $this->_placeholdersEmail());

        if ($template instanceof SmsTemplate)
            $placeholders =  array_merge($placeholders, $this->_placeholdersSMS());

        $placeholders['[trans:translation_key]'] = 'Translation of "translation_key"';

        return $placeholders;
    }

    protected function _placeholdersEmail()
    {
        return [
            '[host]'     => 'Server host',
            '[logo]'     => 'Server logo',
            '[datetime]' => 'Current Date&Time',
        ];
    }

    protected function _placeholdersSMS()
    {
        return [
            '[host]'     => 'Server host',
            '[datetime]' => 'Current Date&Time',
        ];
    }

    protected function _variablesEmail()
    {
        return [
            '[host]'     => Server::url(),
            '[logo]'     => '<img src="'.Appearance::getAssetFileUrl('logo').'" alt="Logo" title="Logo" />',
            '[datetime]' => Formatter::time()->human( Carbon::now() ),
        ];
    }

    protected function _variablesSMS()
    {
        return [
            '[host]'     => Server::url(),
            '[datetime]' => Formatter::time()->human( Carbon::now() ),
        ];
    }

    protected function replaceTransTags(string $input): string
    {
        $pattern = '/\[trans:([^\]]+)\]/';
        $callback = fn (array $matches) => trans($matches[1]);

        return preg_replace_callback($pattern, $callback, $input);
    }
}