<?php

class AfterStoreUpdate
{

    private const MGS_OK = 'УСПІХ';
    private const MGS_ERROR = 'ПОМИЛКА';

    // шлях до composer
    // визначаться системно, але можна вказати шлях, наприклад '/usr/bin/composer'
    private const COMPOSER_PATH = 'composer';

    private array $_cli_color = [
        'error' => "\033[31m",
        'success' => "\033[32m",
        'warning' => "\033[33m",
        'reset' => "\033[0m",
        'strong' => "\033[32m"
    ];

    private string $_toPath;
    private string $_lock_filename;
    private string $_zipUrl = 'https://github.com/zatomant/combacart/archive/refs/heads/main.zip';

    public function __construct()
    {
        $this->_toPath = dirname(__DIR__);
        $this->_lock_filename = __DIR__ . '/lock.php';
    }

    public function run()
    {

        $this->header();

        $stage = $this->getState();

        try {

            if ($stage['current'] == 'checkcomposer') {
                exec(self::COMPOSER_PATH . ' --version', $output, $returnCode);
                if ($returnCode !== 0) {
                    $this->die("Composer не знайдено. Встановіть Composer перед виконанням цього оновлення.\n");
                }
                $this->setState('download');
                $stage = $this->getState();
            }

            if ($stage['current'] == 'download') {
                $tempDir = sys_get_temp_dir() . '/' . uniqid('cc_', true);
                $zipFile = $tempDir . '/combacart.zip';

                // Створюємо тимчасову теку
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0777, true);
                }

                $this->render("Завантаження архіву... ");
                if (!file_put_contents($zipFile, file_get_contents($this->_zipUrl))) {
                    if (!is_writable($tempDir)) {
                        $this->die("Відсутній доступ до тимчасової теки завантаження\n");
                    }
                    $this->die("Завантаження архіву $this->_zipUrl не виконано\n");
                }
                $this->render(self::MGS_OK . "\n", "success");
                $this->setState('unzip', ['zipFile' => $zipFile, 'tempDir' => $tempDir]);
                $stage = $this->getState();
            }

            if ($stage['current'] == 'unzip') {
                echo "Розпакування архіву... ";
                $zip = new ZipArchive;
                if ($zip->open($stage['zipFile']) === TRUE) {
                    if (!$zip->extractTo($stage['tempDir'])) {
                        $this->die("При розпакуванні архіву виникла помилка.\n");
                    }
                    $zip->close();
                } else {
                    $this->die("Не вдалося відкрити ZIP-архів.\n");
                }
                $this->render(self::MGS_OK . "\n", "success");
                $this->setState('copy', ['tempDir' => $stage['tempDir']]);
                $stage = $this->getState();
            }

            if ($stage['current'] == 'copy') {
                $sourceDir = $stage['tempDir'] . "/combacart-main";

                $this->render("Копіювання файлів... ");
                if (is_dir($sourceDir)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($files as $fileinfo) {
                        $targetPath = $this->_toPath . DIRECTORY_SEPARATOR . $files->getSubPathName();
                        if ($fileinfo->isDir()) {
                            if (!is_dir($targetPath)) {
                                if (!mkdir($targetPath, 0777, true)) {
                                    $this->die("Не вдалось створити теку, можливо відсутні права.\n");
                                }
                            }
                        } else {
                            if (!copy($fileinfo, $targetPath)) {
                                $this->die("Не вдалось скопіювати файл, можливо відсутні права.\n");
                            }
                        }
                    }
                } else {
                    $this->die("Не знайдена тека 'combacart-main' в архіві.\n");
                }

                $this->render(self::MGS_OK . "\n", "success");
                $this->setState('composer', ['tempDir' => $stage['tempDir']]);
                $stage = $this->getState();
            }

            if ($stage['current'] == 'composer') {
                chdir($this->_toPath);

                $composerJsonPath = $this->_toPath . '/composer.json';
                if (!file_exists($composerJsonPath)) {
                    $this->die("Немає доступу до composer.json\n");
                }

                $command = sprintf(
                    '%s update --working-dir=%s --prefer-dist --optimize-autoloader',
                    self::COMPOSER_PATH,
                    escapeshellarg($this->_toPath)
                );
                if (PHP_SAPI !== 'cli') {
                    $command .= " --no-interaction --no-ansi 2>&1";
                    $this->render("Встановлення залежностей...\n");
                }
                passthru($command, $returnCode);
                if ($returnCode !== 0) {
                    $this->setState('error', ['msg_stage' => 'composer', 'msg' => 'Опис помилки дивіться у журналі сервера.']);
                    $this->die("Встановлення залежностей не виконано.\n");
                }
                if (PHP_SAPI !== 'cli') {
                    $this->render("\nВстановлення залежностей... ")->render(self::MGS_OK . "\n", "success");
                }
                $this->setState('final');
                $stage = $this->getState();
            }

            if (in_array($stage['current'], ['final', 'ok', 'error'])) {

                if (in_array($stage['current'], ['final', 'ok'])) {
                    $this->setState('ok');
                    if (file_exists(dirname(__DIR__) . '/src/Core/Entity.php')) {
                        if (PHP_SAPI !== 'cli') {
                            include_once(dirname(__DIR__) . '/src/Core/Entity.php');
                            $this->render("Встановлення/оновлення CombaCart завершено. ", "strong")
                                ->render("Можна ")
                                ->render("<a href=\"/" . \Comba\Core\Entity::get('PAGE_COMBA') . "\">перейти</a>", "strong")
                                ->render(" до керування замовленнями.\n\n");
                        } else {
                            $this->render("Встановлення/оновлення CombaCart завершено. ", "strong")
                                ->render("Можна перейти ")
                                ->render('на сторінку /comba',"strong")
                                ->render(" до керування замовленнями.\n\n");
                        }
                    } else {
                        $this->render("Встановлення/оновлення CombaCart завершено.\n", "strong");
                    }
                }
                if (in_array($stage['current'], ['error', 'ok'])) {
                    if ($stage['current'] == 'error') {
                        $_msg = $stage['msg'] ?? '';
                        $_stage = $stage['msg_stage'] ?? '';
                        $this->render("Під час виконання кроку ")->render($_stage, 'strong')->render(" сталася помилка:\n")->render($_msg . "\n", 'error');
                    }
                    $this->render("Щоб знову почати процес оновлення, видаліть файл блокування.\n", 'warning');
                }
                $this->die();
            }

        } catch (Throwable $e) {
            $this->setState('error', ['msg' => $e->getMessage(), 'msg_stage' => $stage['current']]);
            $this->die("\nПід час виконання кроку <strong>" . $stage['current'] . "</strong> сталася помилка, докладніше про це дивіться у файлі журналу сервера.\n");
        }

    }

    private function header()
    {
        if (PHP_SAPI !== 'cli') {
            if (ob_get_level()) {
                ob_end_flush(); // Закрити всі активні буфери
            }
            ob_implicit_flush(true); // Автоматично викликати flush() після кожного виводу
            set_time_limit(0); // таймаут на виконання скрипту
            header("Cache-Control: no-cache, no-store, must-revalidate");
            header("Pragma: no-cache");
            header("Expires: " . gmdate('D, d M Y H:i:s', time() - 3600) . " GMT");
            header('Content-Type: text/event-stream');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
        }
    }

    private function getState(): array
    {
        $data = [];
        if (file_exists($this->_lock_filename)) {
            $str = file_get_contents($this->_lock_filename);
        }
        if (!empty($str)) {
            $str = substr($str, 10);
            $data = json_decode($str, true);
        }

        if (empty($data) || !isset($data['current'])) {
            $data = ["current" => "checkcomposer"];
        }
        return $data;
    }

    private function die(string $str = null, ?string $style = 'error')
    {
        if (!empty($str)) {
            $this->render(self::MGS_ERROR . "\n", $style);
            die($str);
        } else {
            die();
        }
    }

    private function render(string $str, string $style = null): AfterStoreUpdate
    {
        if (PHP_SAPI !== 'cli') {
            if (!empty($style)) {
                $str = '<span class="' . $style . '">' . $str . '</span>' . (substr($str, -1) == "\n" ? "\n" : '');
            }
            $str = str_replace("\n", "<br>", $str);
        } else {
            $str = strip_tags($str);
            if (!empty($style)) {
                $str = $this->_cli_color[$style] . $str . $this->_cli_color['reset'];
            }
        }

        echo $str;
        if (PHP_SAPI !== 'cli') {
            flush();
        }
        return $this;
    }

    private function setState(string $state, array $params = null)
    {
        $data = [
            'current' => $state,
        ];

        if (!empty($params)) {
            $data = array_merge($params, $data);
        }

        if (file_put_contents($this->_lock_filename, '<php exit;' . json_encode($data)) === false) {
            $this->render("\nОновлення файлу блокування... ", 'warning');
            $this->die("Перевірте права на запис. Продовження роботи неможливе.");
        }
    }

    private function log($output)
    {
        file_put_contents(str_replace('lock.php', 'log.php', $this->_lock_filename), $output . "\r\n", FILE_APPEND | LOCK_EX);
    }

    private function extractProblemText($output): string
    {
        $problemText = '';
        $isProblemSection = false;

        $output = is_array($output) ? $output : explode("\n", $output);

        if (is_array($output)) {
            foreach ($output as $line) {
                if (strpos(trim($line), "Problem") === 0 || strpos(trim($line), "Could not parse") === 0) {
                    $isProblemSection = true;
                }
                if ($isProblemSection) {
                    $problemText .= $line . "\n";
                }
                if ($isProblemSection && trim($line) === "") {
                    break;
                }
            }
        }

        return $problemText;
    }
}

(new AfterStoreUpdate())->run();

