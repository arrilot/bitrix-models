<?

namespace Arrilot\BitrixModels;

use Exception;

class Element extends Model
{
    /**
     * Bitrix entity class.
     *
     * @var string
     */
    protected static $entityClass = 'CIBlockElement';

    /**
     * Fetch element fields from database and place them to $this->fields.
     *
     * @return null
     * @throws InvalidModelIdException
     */
    public function fetch()
    {
        $obElement = $this->entity->getByID($this->id)->getNextElement();
        if (!$obElement) {
            throw new InvalidModelIdException();
        }

        $this->fields = $obElement->getFields();

        $this->fields['PROPERTIES'] = $obElement->getProperties();

        $this->setAdditionalFieldsWhileFetching();
    }

    /**
     * Add additional fields to $this->fields if they are not set yet.
     *
     * @return null
     */
    protected function setAdditionalFieldsWhileFetching()
    {
        if (!isset($this->fields['PROPERTY_VALUES']) && !empty($this->fields['PROPERTIES'])) {
            foreach ($this->fields['PROPERTIES'] as $code => $field) {
                $this->fields['PROPERTY_VALUES'][$code] = $field['VALUE'];
            }
        }
    }

    /**
     * Get model fields from cache or database.
     *
     * @return array
     */
    public function get()
    {
        if (!$this->isFetched()) {
            $this->fetch();
        }

        $this->setAdditionalFieldsWhileFetching();

        return $this->fields;
    }

    /**
     * Create new element in database.
     *
     * @param $fields
     *
     * @return static
     * @throws Exception
     */
    public static function create($fields)
    {
        $element = new self::$entityClass;
        $id = $element->add($fields);

        if (!$id) {
            throw new Exception($element->LAST_ERROR);
        }

        $fields['ID'] = $id;

        return new static($id, $fields);
    }

    /**
     * Delete element.
     *
     * @return bool
     */
    public function delete()
    {
        return $this->entity->delete($this->id);
    }

    /**
     * Save model to database.
     *
     * @param array $selectedFields save only these fields instead of all.
     *
     * @return bool
     */
    public function save(array $selectedFields = [])
    {
        $this->get();

        $fields = $this->collectFieldsForSave($selectedFields);

        return $this->entity->update($this->id, $fields);
    }

    /**
     * Create an array of fields that will be saved to database.
     *
     * @param $selectedFields
     *
     * @return array
     */
    protected function collectFieldsForSave($selectedFields)
    {
        $blacklisted = [
            'ID',
            'IBLOCK_ID',
            'PROPERTIES'
        ];

        $fields = [];
        foreach ($this->fields as $field => $value) {
            // skip if is not in selected fields
            if ($selectedFields && !in_array($field, $selectedFields)) {
                continue;
            }

            // skip blacklisted fields
            if (in_array($field, $blacklisted)) {
                continue;
            }

            // skip trash fields
            if ($value === '' || substr($field, 0, 1) === '~') {
                continue;
            }

            $fields[$field] = $value;
        }

        return $fields;
    }
}
