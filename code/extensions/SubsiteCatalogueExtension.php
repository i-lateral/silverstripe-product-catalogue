<?php

/**
 * Adds some basic subsites options that can be added to all commerce objects
 */
class SubsiteCatalogueExtension extends DataExtension
{
    private static $has_one=array(
        'Subsite' => 'Subsite', // The subsite that this page belongs to
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            "Root.Main",
            HiddenField::create(
                'SubsiteID',
                'SubsiteID',
                Subsite::currentSubsiteID()
            )
        );
    }

    /**
     * Update any requests to limit the results to the current site
     */
    public function augmentSQL(SQLQuery &$query, DataQuery &$dataQuery = null)
    {
        if (Subsite::$disable_subsite_filter) {
            return;
        }
        if ($dataQuery->getQueryParam('Subsite.filter') === false) {
            return;
        }
        
        // If you're querying by ID, ignore the sub-site - this is a bit ugly...
        // if(!$query->where || (strpos($query->where[0], ".\"ID\" = ") === false && strpos($query->where[0], ".`ID` = ") === false && strpos($query->where[0], ".ID = ") === false && strpos($query->where[0], "ID = ") !== 0)) {
        if ($query->filtersOnID()) {
            return;
        }

        if (Subsite::$force_subsite) {
            $subsiteID = Subsite::$force_subsite;
        } else {
            /*if($context = DataObject::context_obj()) $subsiteID = (int)$context->SubsiteID;
            else */$subsiteID = (int)Subsite::currentSubsiteID();
        }

        // The foreach is an ugly way of getting the first key :-)
        foreach ($query->getFrom() as $tableName => $info) {
            // The tableName should be SiteTree or SiteTree_Live...
            if (strpos($tableName, 'SiteTree') === false) {
                break;
            }
            $query->addWhere("\"$tableName\".\"SubsiteID\" IN ($subsiteID)");
            break;
        }
    }
    
    public function onBeforeWrite()
    {
        if (!$this->owner->ID && !$this->owner->SubsiteID) {
            $this->owner->SubsiteID = Subsite::currentSubsiteID();
        }
        
        parent::onBeforeWrite();
    }
}
