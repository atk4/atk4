<?php
/**
 * Implementation of Generic Tabs. Use class "View_jUITabs"
 * or better yet - simply "Tabs" instead.
 */
class View_Tabs extends View
{
    public function setController($c)
    {
        parent::setController($c);
        if ($this->getController()) {
            // add tabs from controller
            $data = $this->getController()->getRows(array('id', 'name', 'content'));
            foreach ($data as $row) {
                $t = $this->addTab($row['id'], $row['name']);
                $t->set($row['content']);
            }
        }

        return $this;
    }
}
