<?

namespace Arrilot\BitrixModels;

use CIBlockElement;

abstract class Element extends Model
{
    /**
     * Fetch model fields from database and save them to $this->fields.
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
}
