<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.installer.installer');

class PlgSystemMaxBotInstallerScript
{
    public function preflight($type, $parent)
    {
        if (!in_array((string) $type, array('install', 'update', 'discover_install'), true)) {
            return;
        }

        $this->installBundledOrderPlugin($parent);
    }

    private function installBundledOrderPlugin($parent)
    {
        $sourceRoot = $this->resolveSourceRoot($parent);
        $orderPath = rtrim($sourceRoot, '/\\') . '/bundled/vmpayment/order';
        if (!JFolder::exists($orderPath)) {
            return;
        }

        $xmlPath = $orderPath . '/order.xml';
        if (!JFile::exists($xmlPath)) {
            return;
        }

        try {
            $installer = new JInstaller();
            $installer->install($orderPath);
        } catch (\Throwable $e) {
            // Не блокируем установку основного плагина
        }
    }

    private function resolveSourceRoot($parent)
    {
        if (!is_object($parent) || !method_exists($parent, 'getParent')) {
            return dirname(__FILE__);
        }

        $installer = $parent->getParent();
        if (!is_object($installer) || !method_exists($installer, 'getPath')) {
            return dirname(__FILE__);
        }

        $sourceRoot = $installer->getPath('source');
        if (!is_string($sourceRoot) || $sourceRoot === '') {
            return dirname(__FILE__);
        }

        return $sourceRoot;
    }
}
