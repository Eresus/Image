Использование с модулем ORM
===========================

Модуль Image можно использовать для создания у объектов ORM_Entity свойств, хранящих изображения.

Далее, на примерах, мы добавим свойство `image`.

Изменение класса таблицы
------------------------

В описание таблицы надо добавить поле для хранения расширения файла

    public function setTableDefinition()
    {
        …
        $this->hasColumns(array(
            …
            'image' => array(
                'type' => 'string',
                'length' => 3, // Image всегда приводит расширение файла к 3-хсимвольному
            ),

Изменение класса сущности
-------------------------

Добавим приватное свойство `newImage`, где будем хранить загруженное изображение, до сохранения
объекта в БД:

    /**
     * @var null|Image_Object
     */
    private $newImage = null;

Создадим метод, составляющий имя файла изображения:

    /**
     * @param string $ext расширение
     *
     * @return string  полное имя файла
     */
    private function composeImagePath($ext = 'tmp')
    {
        $path = $this->getTable()->getPlugin()->getDataDir() . $this->id . '.' . $ext;
        return $path;
    }

Добавим геттер для получения значения свойства `image`:

    /**
     * @return Image_Object
     */
    protected function getImage()
    {
        $filename = $this->composeImagePath($this->getPdoValue('image'));
        return file_exists($filename) ? new Image_Object($filename) : null;
    }

И сеттер, для загрузки изображений:

    /**
     * @param array $uploaded  информация о файле из $_FILES
     */
    protected function setImage(array $uploaded)
    {
        $this->newImage = Image_Object::createFromUploaded($uploaded);
    }

Добавим в метод `afterSave` действия по сохранению загруженной картинки:

    public function afterSave()
    {
        …
        if ($this->newImage)
        {
            $this->newImage->moveTo($this->composeLogoPath($this->newImage->getType()));
            $this->newImage->save();
            $this->setPdoValue('image', $this->newImage->getType());
            $this->newImage = null;
            $this->getTable()->update($this);
        }

При удалении объекта не забываем удалять картинку:

    public function afterDelete()
    {
        if ($this->image)
        {
            $this->image->delete();
        }
