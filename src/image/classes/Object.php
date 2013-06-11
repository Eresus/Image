<?php
/**
 * Объект изображения
 *
 * @version ${product.version}
 *
 * @copyright 2011, Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Михаил Красильников <m.krasilnikov@yandex.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 *
 * @package Image
 */

/**
 * Объект изображения
 *
 * @property-read string $path  полный файловый путь к файлу
 * @property-read string $url   полный URL картинки
 *
 * @package Image
 * @since 1.00
 */
class Image_Object
{
    /**
     * Константы углов изображения
     *
     * @var string
     */
    const CORNER_TOP_LEFT = 'TL';
    const CORNER_TOP_RIGHT = 'TR';
    const CORNER_BOTTOM_RIGHT = 'BR';
    const CORNER_BOTTOM_LEFT = 'BL';

    /**
     * Список поддерживаемых форматов
     *
     * @var string[]
     * @since 1.00
     */
    private $supportedMimeTypes = array(
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    );

    /**
     * Имя файла
     *
     * @var string
     */
    private $path;

    /**
     * Существует ли этот файл
     *
     * @var bool
     */
    private $exists = false;

    /**
     * Очередь действий
     *
     * @var string
     */
    private $actionQueue = array();

    /**
     * Объект для работы с картинкой
     *
     * @var GdThumb
     */
    private $phpThumb = null;

    /**
     * Кэш миниатюр
     *
     * @var array
     */
    private $thumbs = array();

    /**
     * Кэш информации о картинке
     *
     * @var array
     */
    private $info;

    /**
     * Конструктор
     *
     * @param string $path
     *
     * @return Image_Object
     *
     * @since 1.00
     */
    public function __construct($path = null)
    {
        $this->path = $path;
        $this->exists = file_exists($this->path);
    }

    /**
     * Геттер псевдосвойств
     *
     * @param string $key
     *
     * @return mixed
     *
     * @since 1.00
     */
    public function __get($key)
    {
        switch ($key)
        {
            case 'path':
                return $this->path;
                break;
            case 'url':
                $cms = Eresus_Kernel::app();
                $legacyKernel = $cms->getLegacyKernel();
                return $legacyKernel->root . substr($this->path, strlen($cms->getFsRoot()));
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Устанавливает имя файла
     *
     * @param string $path
     *
     * @return void
     *
     * @since 1.00
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Возвращает true если картинка изменена, но изменения не сохранены
     *
     * @return bool
     *
     * @since 2.01
     */
    public function isChanged()
    {
        return count($this->actionQueue) > 0;
    }

    /**
     * Загружает файл по описанию из $_FILES
     *
     * @param array $info элемент массива $_FILES
     *
     * @throws DomainException  если у файла слишком большой размер, если он был загружен частично
     *                          или если у файла неподдерживаемый тип
     *
     * @return bool  true если файл был загружен пользователем и false, если не был
     *
     * @since 1.00
     */
    public function upload(array $info)
    {
        switch ($info['error'])
        {
            case UPLOAD_ERR_NO_FILE:
                if (!$this->exists)
                {
                    $this->path = null;
                }
                return false;
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new DomainException(
                    'Размер загружаемого файла превышает максимально допустимый');
                break;
            case UPLOAD_ERR_PARTIAL:
                throw new DomainException(
                    'Во время загрузки файла произошёл сбой. Попробуйте ещё раз');
                break;
        }

        if (!in_array($info['type'], array_keys($this->supportedMimeTypes)))
        {
            throw new DomainException("Неподдерживаемый тип файла: {$info['type']}.");
        }

        if ($this->exists)
        {
            $this->addAction('delete');
        }

        /* Устанавливаем правильное расширение файла */
        $newExt = $this->supportedMimeTypes[$info['type']];
        $this->path = substr_replace($this->path, $newExt, strrpos($this->path, '.') + 1);

        $this->addAction('upload', $info);
        return true;
    }

    /**
     * Изменяет размер картинки
     *
     * @param int $width   ширина в пикселях
     * @param int $height  высота в пикселях
     *
     * @return Image_Object
     *
     * @since 1.00
     */
    public function resize($width, $height)
    {
        $this->addAction('resize', $width, $height);
        return $this;
    }

    /**
     * Создаёт миниатюру для картинки
     *
     * @param string $name    имя миниатюры (null — для миниатюры по умолчанию)
     * @param int    $width   ширина в пикселях
     * @param int    $height  высота в пикселях
     *
     * @return Image_Object
     *
     * @since 1.00
     */
    public function createThumbnail($name, $width, $height)
    {
        $this->addAction('thumbnail', $name, $width, $height);
        return $this;
    }

    /**
     * Накладывает на изображение другое изображение
     *
     * @param string $filename  имя файла накладываемого изображения
     * @param string $origin    положение накладываемого файла (см. POS_XX)
     * @param int    $padX      отступ от края по оси X
     * @param int    $padY      отступ от края по оси Y
     *
     * @return Image_Object
     *
     * @since 1.00
     */
    public function overlay($filename, $origin, $padX, $padY)
    {
        $this->addAction('overlay', $filename, $origin, $padX, $padY);
        return $this;
    }

    /**
     * Удаляет файлы изображения
     *
     * @return void
     *
     * @since 1.00
     */
    public function delete()
    {
        $this->actionQueue = array();
        $this->addAction('delete');
        $this->save();
    }

    /**
     * Сохраняет изменения изображения
     *
     * @throws LogicException
     *
     * @return void
     *
     * @since 1.00
     */
    public function save()
    {
        if (!$this->path)
        {
            return;
        }
        while (count($this->actionQueue))
        {
            $action = array_shift($this->actionQueue);
            $methodName = 'action' . $action['action'];
            if (!method_exists($this, $methodName))
            {
                throw new LogicException('Unknown action: ' . $action['action']);
            }
            call_user_func_array(array($this, $methodName), $action['args']);
        }
        if ($this->phpThumb)
        {
            $this->phpThumb->save($this->path);
        }
    }

    /**
     * Возвращает объект запрошенной миниатюры
     *
     * @param string $name  имя миниатюры (не файла миниатюры!) или null для миниатюры по умолчанию
     *
     * @return Image_Thumbnail
     *
     * @since 1.00
     */
    public function thumb($name = null)
    {
        if (!isset($this->thumbs[$name]))
        {
            $this->thumbs[$name] = new Image_Thumbnail($this->getThumbName($name));
        }
        return $this->thumbs[$name];
    }

    /**
     * Возвращает ширину картинки в пикселях
     *
     * @return int
     *
     * @since 1.00
     */
    public function getWidth()
    {
        $this->readInfo();
        return $this->info['width'];
    }

    /**
     * Возвращает высоту картинки в пикселях
     *
     * @return int
     *
     * @since 1.00
     */
    public function getHeight()
    {
        $this->readInfo();
        return $this->info['height'];
    }

    /**
     * Добавляет действие над изображением в очередь действий
     *
     * @param string $action  действие
     * @param ...             дополнительные аргументы действия
     *
     * @return void
     *
     * @since 1.00
     */
    protected function addAction($action)
    {
        $args = func_get_args();
        array_shift($args);
        $this->actionQueue []= array('action' => $action, 'args' => $args);
    }

    /**
     * Возвращает объект PhpThumb для текущей картинки
     *
     * Инициализирует библиотеку PhpThumb.
     *
     * @return GdThumb
     *
     * @since 1.00
     */
    protected function getPhpThumb()
    {
        if (!class_exists('PhpThumbFactory', false))
        {
            /** @noinspection PhpIncludeInspection */
            include __DIR__ . '/../phpthumb/ThumbLib.inc.php';
        }
        if (!$this->phpThumb)
        {
            $this->phpThumb = PhpThumbFactory::create($this->path);
        }
        return $this->phpThumb;
    }

    /**
     * Возвращает имя файла миниатюры по её имени
     *
     * @param string|null $name
     *
     * @return string
     *
     * @since 1.00
     */
    protected function getThumbName($name)
    {
        $thumbName = $name === null ? '' : '-' . $name;
        $filename = substr_replace($this->path, '-thumb' . $thumbName . '.png',
            strrpos($this->path, '.'));
        return $filename;
    }

    /**
     * Удалаяет все файлы этого изображения
     *
     * @return void
     *
     * @since 1.00
     */
    private function actionDelete()
    {
        $baseName = substr($this->path, 0, strrpos($this->path, '.'));
        $files = glob($baseName . '.*');
        if (!is_array($files))
        {
            $files = array();
        }
        $files2 = glob($baseName . '-*');
        if (!is_array($files2))
        {
            $files2 = array();
        }

        $files = array_merge($files, $files2);

        foreach ($files as $file)
        {
            @unlink($file);
        }
    }

    /**
     * Загружает файл
     *
     * @param array $info
     *
     * @throws RuntimeException  если $info['tmp_file'] не указывает на правильный загруженный файл
     * @throws RuntimeException  если не удаётся создать промежуточную директорию
     *
     * @return void
     *
     * @since 1.00
     */
    private function actionUpload($info)
    {
        if (!is_uploaded_file($info['tmp_name']))
        {
            throw new RuntimeException('Not valid uploaded file ' . $info['tmp_name']);
        }

        $root = Eresus_Kernel::app()->getFsRoot();
        $dirs = explode('/', substr(dirname($this->path), strlen($root)));
        foreach ($dirs as $dir)
        {
            $path = $root . '/' . $dir;
            if (!file_exists($path))
            {
                $umask = umask(0000);
                try
                {
                    mkdir($path, 0777);
                }
                catch (Exception $e)
                {
                    umask($umask);
                    throw new RuntimeException(sprintf('Can\'t create directory "%s": %s', $path,
                        $e->getMessage()));
                }
                umask($umask);
            }
            $root = $path;
        }

        if (!move_uploaded_file($info['tmp_name'], $this->path))
        {
            throw new RuntimeException('Can not move uploaded file to ' . $this->path);
        }

        @chmod($this->path, 0666);
    }

    /**
     * Изменяет размер картинки
     *
     * @param int $width
     * @param int $height
     *
     * @return void
     *
     * @since 1.00
     */
    private function actionResize($width, $height)
    {
        $image = $this->getPhpThumb();
        $image->resize($width, $height);
    }

    /**
     * Изменяет миниатюру картинки
     *
     * @param string $name
     * @param int    $width
     * @param int    $height
     *
     * @return void
     *
     * @since 1.00
     */
    private function actionThumbnail($name, $width, $height)
    {
        $target = $this->getThumbName($name);
        $image = clone $this->getPhpThumb();
        $image->resize($width, $height);
        $image->save($target);
        @chmod($target, 0666);
    }

    /**
     * Накладывает на изображение другое изображение
     *
     * @param string $filename  имя файла накладываемого изображения
     * @param string $origin    положение накладываемого файла (см. POS_XX)
     * @param int    $padX      отступ от края по оси X
     * @param int    $padY      отступ от края по оси Y
     *
     * @throws LogicException
     *
     * @return void
     *
     * @since 1.00
     */
    private function actionOverlay($filename, $origin, $padX, $padY)
    {
        if (!file_exists($filename))
        {
            return;
        }

        $image = $this->getPhpThumb();
        $dst = $image->getOldImage();
        $overlay = PhpThumbFactory::create($filename);
        $src = $overlay->getOldImage();

        $dw = imageSX($dst);
        $dh = imageSY($dst);
        $sw = imageSX($src);
        $sh = imageSY($src);

        switch ($origin)
        {
            case self::CORNER_TOP_LEFT:
                $x = $padX;
                $y = $padY;
                break;
            case self::CORNER_TOP_RIGHT:
                $x = $dw - $sw - $padX;
                $y = $padY;
                break;
            case self::CORNER_BOTTOM_LEFT:
                $x = $padX;
                $y = $dh - $sh - $padY;
                break;
            case self::CORNER_BOTTOM_RIGHT:
                $x = $dw - $sw - $padX;
                $y = $dh - $sh - $padY;
                break;
            default:
                throw new LogicException('Unknown origin');
        }
        imagecopy($dst, $src, $x, $y, 0, 0, $sw, $sh);
        $image->setOldImage($dst);
    }

    /**
     * Читает информацию об изображении
     *
     * @return void
     *
     * @since 1.00
     */
    private function readInfo()
    {
        if (!$this->info)
        {
            $info = getimagesize($this->path);
            $this->info = array(
                'width' => $info[0],
                'height' => $info[1],
            );
        }
    }
}

