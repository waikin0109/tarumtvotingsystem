<?php
namespace Library;

class SimplePager {
    public $limit;      // Page size
    public $page;       // Current page
    public $item_count; // Total item count
    public $page_count; // Total page count
    public $result;     // Result set (array of records)
    public $count;      // Item count on the current page

    public function __construct($db, $query, $params = [], $limit = 10, $page = 1) {
        // Set [limit] and [page]
        $this->limit = is_numeric($limit) ? max((int)$limit, 1) : 10;
        $this->page = is_numeric($page) ? max((int)$page, 1) : 1;

        // Set [item count]
        $countQuery = preg_replace('/SELECT.+FROM/', 'SELECT COUNT(*) FROM', $query, 1);
        $stm = $db->prepare($countQuery);
        $stm->execute($params);
        $this->item_count = $stm->fetchColumn();

        // Set [page count]
        $this->page_count = ceil($this->item_count / $this->limit);

        // Calculate offset
        $offset = ($this->page - 1) * $this->limit;

        // Set [result]
        $stm = $db->prepare($query . " LIMIT $offset, $this->limit");
        $stm->execute($params);
        $this->result = $stm->fetchAll();

        // Set [count]
        $this->count = count($this->result);
    }

    public function html($href = '', $attr = '')
{
    if (!$this->result || $this->page_count < 1) {
        return;
    }

    // Normalise extra query params (q, status, etc.)
    $queryBase = '';
    if ($href !== '') {
        $queryBase = '&' . ltrim($href, '&?');
    }

    $prev = max($this->page - 1, 1);
    $next = min($this->page + 1, $this->page_count);

    echo "<nav $attr>";
    echo "<ul class=\"pagination pagination-sm mb-0\">";

    // Previous button
    $disabledPrev = $this->page <= 1 ? ' disabled' : '';
    echo "<li class=\"page-item{$disabledPrev}\">";
    echo    "<a class=\"page-link\" href=\"?page={$prev}{$queryBase}\" tabindex=\"-1\" aria-label=\"Previous\">";
    echo        "&laquo;";
    echo    "</a>";
    echo "</li>";

    // Page numbers
    for ($p = 1; $p <= $this->page_count; $p++) {
        $active = $p == $this->page ? ' active' : '';
        echo "<li class=\"page-item{$active}\">";
        echo    "<a class=\"page-link\" href=\"?page={$p}{$queryBase}\">{$p}</a>";
        echo "</li>";
    }

    // Next button
    $disabledNext = $this->page >= $this->page_count ? ' disabled' : '';
    echo "<li class=\"page-item{$disabledNext}\">";
    echo    "<a class=\"page-link\" href=\"?page={$next}{$queryBase}\" aria-label=\"Next\">";
    echo        "&raquo;";
    echo    "</a>";
    echo "</li>";

    echo "</ul>";
    echo "</nav>";
}

}