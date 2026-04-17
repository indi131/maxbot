<?php
// defined('_JEXEC') or die;

// class PlgSystemTelegramBotInstallerScript
// {
//     public function preflight($type, $parent)
//     {
//         // Путь к исходному файлу Mail.php
//         $originalMailFile = JPATH_LIBRARIES . '/src/Mail/Mail.php';

//         // Путь к новому файлу Mail.php, который будет скопирован
//         $newMailFile = JPATH_PLUGINS . '/system/TelegramBot/Mail/Mail.php';

//         // Путь к резервному файлу MailOld.php
//         $backupMailFile = JPATH_LIBRARIES . '/src/Mail/MailOld.php';

//         // Переименование исходного файла в MailOld.php
//         if (JFile::exists($originalMailFile)) {
//             JFile::move($originalMailFile, $backupMailFile);
//         }

//         // Копирование нового файла Mail.php на место
//         if (JFile::exists($newMailFile)) {
//             JFile::copy($newMailFile, $originalMailFile);
//         }
//     }
// }


defined('_JEXEC') or die;

class PlgSystemTelegramBotInstallerScript
{
    public function preflight($type, $parent)
    {
        // Путь к исходному файлу Mail.php
        $originalMailFile = JPATH_LIBRARIES . '/src/Mail/Mail.php';

        // Путь к новому файлу Mail.php, который будет скопирован
        $newMailFile = JPATH_PLUGINS . '/system/TelegramBot/Mail/Mail.php';

        // Путь к резервному файлу MailOld.php
        $backupMailFile = JPATH_LIBRARIES . '/src/Mail/MailOld.php';

        // Копирование нового файла Mail.php на место
        if (JFile::exists($newMailFile)) {
            if (JFile::exists($originalMailFile)) {
                // Переименование исходного файла в MailOld.php
                JFile::move($originalMailFile, $backupMailFile);
            }
            JFile::copy($newMailFile, $originalMailFile);
        }
    }
}