<?php
defined('_JEXEC') or die;

class PlgSystemTelegramBot extends JPlugin
{
    protected $app;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->app = JFactory::getApplication();
    }

    public function onAfterInitialise()
    {
    }

    public function onExtensionBeforeSave($context, $table, $isNew)
    {
$this->app->enqueueMessage('$table->params: ' . gettype($table->params), 'debug');
       
        // Проверяем, что это сохранение настроек нашего плагина
        if ($context == 'com_plugins.plugin' && $table->element == 'TelegramBot')
        {
            $params = json_decode($table->params, true);
            $password = $params['password'];
            $email = $params['email'];
            $server = $params['server'];

            $this->saveSettings($password, $email, $server);
        }
    }

    public function saveSettings($password, $email, $server)
    {
        
         $this->app->enqueueMessage('saveSettings() is being called', 'debug');
        
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Получаем префикс таблицы
        $prefix = $db->getPrefix();
        $tableName = $prefix . 'telegrambot_settings';

        // Проверяем, есть ли уже запись в таблице
        $query->select('*')
            ->from($db->quoteName($tableName));
        $db->setQuery($query);
        $existingRecord = $db->loadObject();

        if ($existingRecord)
        {
            // Обновляем существующую запись
            $query = $db->getQuery(true);
            $query->update($db->quoteName($tableName))
                ->set($db->quoteName('password') . ' = ' . $db->quote($password))
                ->set($db->quoteName('email') . ' = ' . $db->quote($email))
                ->set($db->quoteName('server') . ' = ' . $db->quote($server));
            $db->setQuery($query);
            $db->execute();
        }
        else
        {
            // Создаем новую запись
            $query->insert($db->quoteName($tableName))
                ->columns(array($db->quoteName('password'), $db->quoteName('email'), $db->quoteName('server')))
                ->values($db->quote($password) . ', ' . $db->quote($email) . ', ' . $db->quote($server));
            $db->setQuery($query);
            $db->execute();
        }
    }


    public function onExtensionAfterUninstall($installer, $eid)
    {

        $originalMailFile = JPATH_LIBRARIES . '/src/Mail/Mail.php';
        $backupMailFile = JPATH_LIBRARIES . '/src/Mail/MailOld.php';

    // Удаление исходного файла Mail.php, если он существует
    if (JFile::exists($originalMailFile)) {
        JFile::delete($originalMailFile);
    }

    // Переименование резервного файла MailOld.php в Mail.php, если он существует
    if (JFile::exists($backupMailFile)) {
        JFile::move($backupMailFile, $originalMailFile);
    }
       
    }

}
