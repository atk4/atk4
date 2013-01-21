<?php
/**
 * Agile Toolkit Coding Standard
 *
 * PHP version 5
 *
 * @category PHP
 * @package  None
 * @author   Romans Malinovskis <romans@agiletoolkit.org>
 * @license  AGPL http://www.gnu.org/licenses/agpl-3.0.html
 * @version  $Id: $
 * @link     http://agiletoolkit.org/
 */

if (class_exists('PHP_CodeSniffer_Standards_CodingStandard', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_CodingStandard not found');
}

/**
 * Agile Toolkit Coding Standard
 *
 * PHP version 5
 *
 * @category PHP
 * @package  None
 * @author   Romans Malinovskis <romans@agiletoolkit.org>
 * @license  AGPL http://www.gnu.org/licenses/agpl-3.0.html
 * @link     http://agiletoolkit.org/
 */

class PHP_CodeSniffer_Standards_KingKludge_KingKludgeCodingStandard
extends PHP_CodeSniffer_Standards_CodingStandard
{
    /**
     * Return a list of external sniffs to include with this standard.
     *
     * The standard can include the whole standards or individual Sniffs.
     *
     * @return array
     */
    public function getIncludedSniffs()
    {
        return array();

    }//end getIncludedSniffs()

    /**
     * Return a list of external sniffs to exclude from this standard.
     *
     * Including a whole standards above, individual Sniffs can then be removed here.
     *
     * @return array
     */
    public function getExcludedSniffs()
    {
        return array();

    }//end getExcludedSniffs()
}

