<?php
class MySQLPagedResultSet {
	
	var $results;
    var $pageSize;
    var $page;
    var $row;

    function MySQLPagedResultSet($result) {
		$resultpage = (isset($_GET['rp'])) ? $_GET['rp'] : "";
	    $this->results = $result;
        $this->pageSize = 20;
        if ((int)$resultpage <= 0)
            $resultpage = 1;
        if ($resultpage > $this->getNumPages())
            $resultpage = $this->getNumPages();
        $this->setPageNum($resultpage);
    }

    function getNumPages() {
        if (!$this->results)
            return false;

        return ceil(mysql_num_rows($this->results) / (float)$this->pageSize);
    }
    
    function getNumRecords() {
		if (!$this->results)
            return false;

        return mysql_num_rows($this->results);
    }

    function setPageNum($pageNum) {
        if ($pageNum > $this->getNumPages() or $pageNum <= 0)
            return false;

        $this->page = $pageNum;
        $this->row = 0;
        mysql_data_seek($this->results, ($pageNum - 1) * $this->pageSize);
    }

    function getPageNum() {
        return $this->page;
    }

    function isLastPage() {
        return ($this->page >= $this->getNumPages());
    }

    function isFirstPage() {
        return ($this->page <= 1);
    }

    function fetchArray() {
        if (!$this->results)
            return false;
        if ($this->row >= $this->pageSize)
            return false;
        $this->row++;
        return mysql_fetch_array($this->results);
    }

    function getPageNav($queryvars = '') {
        $nav = '';
        ($queryvars == '') ? $tempQV ='' : $tempQV = '&' . $queryvars;
        
        if (!$this->isFirstPage()) {
            $nav .= "<a href=\"?rp=".($this->getPageNum() - 1).$tempQV.'">Prev</a>&nbsp;&nbsp;&nbsp;';
        }
        if ($this->getNumPages() > 1)
            for ($i = 1; $i <= $this->getNumPages(); $i++) {
           		$rS = ($i*$this->pageSize)-($this->pageSize-1);
				if ($i == $this->getNumPages()) {
					$rE = $this->getNumRecords();
				} else {
					$rE = $i*$this->pageSize;				
				}
				$paging = $rS."-".$rE;
                if ($i == $this->page) {
					$nav .= "<span class=\"regLB2\">$paging</span>&nbsp;&nbsp;&nbsp;";
                } else {
                	$nav .= "<a href=\"?rp={$i}".$tempQV."\">{$paging}</a>&nbsp;&nbsp;&nbsp;";
                }
            }
        if (!$this->isLastPage()) {
            $nav .= "<a href=\"?rp=".($this->getPageNum() + 1).$tempQV.'">Next</a>';
        }
        return $nav;
    }
}
?>