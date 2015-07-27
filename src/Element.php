<?

namespace Arrilot\BitrixModels;

use CIBlockElement;

abstract class Element extends Model
{
    /**
     * Fetch element fields from database and place them to $this->fields.
     *
     * @return null
     * @throws InvalidModelIdException
     */
    public function fetch()
    {
        $obElement = CIBlockElement::getByID($this->id)->getNextElement();
        if (!$obElement) {
            throw new InvalidModelIdException();
        }

        $this->fields = $obElement->getFields();
        $this->fields['PROPERTIES'] = $obElement->getProperties();
    }

    /**
     * Delete element.
     *
     * @return bool
     */
    public function delete()
    {
        return (new CIBlockElement)->delete($this->id);
    }
}
