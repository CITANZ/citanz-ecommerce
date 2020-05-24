<?php


namespace Cita\eCommerce\Forms\GridField;

use Cita\eCommerce\Model\Variant;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use LogicException;

class ExistingProductAdder extends GridFieldAddExistingAutocompleter
{

    public function doSearch($gridField, $request)
    {
        $dataClass = $gridField->getModelClass();
        $allList = $this->searchList ? $this->searchList : DataList::create($dataClass);

        $searchFields = ($this->getSearchFields())
            ? $this->getSearchFields()
            : $this->scaffoldSearchFields($dataClass);
        if (!$searchFields) {
            throw new LogicException(
                sprintf(
                    'GridFieldAddExistingAutocompleter: No searchable fields could be found for class "%s"',
                    $dataClass
                )
            );
        }

        $params = array();
        foreach ($searchFields as $searchField) {
            $name = (strpos($searchField, ':') !== false) ? $searchField : "$searchField:StartsWith";
            $params[$name] = $request->getVar('gridfield_relationsearch');
        }
        $results = $allList
            ->subtract($gridField->getList())
            ->filterAny($params)
            ->sort(strtok($searchFields[0], ':'), 'ASC')
            ->limit($this->getResultsLimit());


        if ($results->count() == 0) {
            $sku = trim($request->getVar('gridfield_relationsearch'));

            if (!empty($sku)) {
                if ($variant = Variant::get()->filter(['SKU' => $sku])->first()) {
                    if ($pid = $variant->ProductID) {
                        $allList = $allList->subtract($gridField->getList());
                        $results = $allList->filter(['ID' => $pid]);
                    }
                }
            }
        }

        $json = array();
        Config::nest();
        SSViewer::config()->update('source_file_comments', false);
        $viewer = SSViewer::fromString($this->resultsFormat);
        foreach ($results as $result) {
            $title = Convert::html2raw($viewer->process($result));
            $json[] = array(
                'label' => $title,
                'value' => $title,
                'id' => $result->ID,
            );
        }
        Config::unnest();
        $response = new HTTPResponse(json_encode($json));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }
}
