<?php
defined('_JEXEC') or die;

class PlgSystemMaxBot extends JPlugin
{
    protected $app;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->app = JFactory::getApplication();
    }

    public function onExtensionBeforeSave($context, $table, $isNew)
    {
        if ($context !== 'com_plugins.plugin' || !isset($table->element)) {
            return;
        }

        if ($table->element !== 'MaxBot') {
            return;
        }

        $params = json_decode((string) $table->params, true);
        if (!is_array($params)) {
            return;
        }

        $password = isset($params['password']) ? (string) $params['password'] : '';
        $email = isset($params['email']) ? (string) $params['email'] : '';
        $server = isset($params['server']) ? (string) $params['server'] : '';

        $this->saveSettings($password, $email, $server);
    }

    public function saveSettings($password, $email, $server)
    {
        $db = JFactory::getDbo();
        $tableName = '#__maxbot_settings';

        $query = $db->getQuery(true)->select('*')->from($db->quoteName($tableName));
        $db->setQuery($query);
        $existingRecord = $db->loadObject();

        if ($existingRecord) {
            $query = $db->getQuery(true)
                ->update($db->quoteName($tableName))
                ->set($db->quoteName('password') . ' = ' . $db->quote($password))
                ->set($db->quoteName('email') . ' = ' . $db->quote($email))
                ->set($db->quoteName('server') . ' = ' . $db->quote($server));
            $db->setQuery($query);
            $db->execute();
            return;
        }

        $query = $db->getQuery(true)
            ->insert($db->quoteName($tableName))
            ->columns(array($db->quoteName('password'), $db->quoteName('email'), $db->quoteName('server')))
            ->values($db->quote($password) . ', ' . $db->quote($email) . ', ' . $db->quote($server));
        $db->setQuery($query);
        $db->execute();
    }
}
