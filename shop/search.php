<?php
include_once('./_common.php');

if (G5_IS_MOBILE) {
    include_once(G5_MSHOP_PATH.'/search.php');
    return;
}

$g5['title'] = "상품 검색 결과";
include_once('./_head.php');

// QUERY 문에 공통적으로 들어가는 내용
// 상품명에 검색어가 포한된것과 상품판매가능인것만
$sql_common = " from {$g5['g5_shop_item_table']} a, {$g5['g5_shop_category_table']} b ";

$where = array();
$where[] = " (a.ca_id = b.ca_id and a.it_use = 1 and b.ca_use = 1) ";

$search_all = true;
// 상세검색 이라면
if (isset($_GET['qname']) || isset($_GET['qexplan']) || isset($_GET['qid']))
    $search_all = false;

$q       = utf8_strcut(trim($_GET['q']), 30, "");
$qname   = trim($_GET['qname']);
$qexplan = trim($_GET['qexplan']);
$qid     = trim($_GET['qid']);
$qcaid   = trim($_GET['qcaid']);
$qfrom   = trim($_GET['qfrom']);
$qto     = trim($_GET['qto']);
$qsort   = trim($_GET['qsort']);

if ($q) {
    $arr = explode(" ", $q);
    $detail_where = array();
    for ($i=0; $i<count($arr); $i++) {
        $word = trim($arr[$i]);
        if (!$word) continue;

        $concat = array();
        if ($search_all || $qname)
            $concat[] = "a.it_name";
        if ($search_all || $qexplan)
            $concat[] = "a.it_explan2";
        if ($search_all || $qid)
            $concat[] = "a.it_id";
        $concat_fields = "concat(".implode(",' ',",$concat).")";

        $detail_where[] = $concat_fields." like '%$word%' ";

        // 인기검색어
        $sql = " insert into {$g5['popular_table']} set pp_word = '$word', pp_date = '".G5_TIME_YMD."', pp_ip = '{$_SERVER['REMOTE_ADDR']}' ";
        sql_query($sql, FALSE);
    }

    $where[] = "(".implode(" and ", $detail_where).")";
}

if ($qcaid)
    $where[] = " a.ca_id like '$qcaid%' ";

if ($qfrom || $qto)
    $where[] = " a.it_price between '$qfrom' and '$qto' ";

$sql_where = " where " . implode(" and ", $where);

// 상품 출력순서가 있다면
$qsort  = strtolower($qsort);
$qorder = strtolower($qorder);
$order_by = "";
// 아래의 $qsort 필드만 정렬이 가능하게 하여 다른 필드로 하여금 유추해 볼수 없게함
if (($qsort == "it_sum_qty" || $qsort == "it_price" || $qsort == "it_use_avg" || $qsort == "it_use_cnt" || $qsort == "it_update_time") &&
    ($qorder == "asc" || $qorder == "desc")) {
    $order_by = ' order by ' . $qsort . ' ' . $qorder . ' , it_order, it_id desc';
}

// 총몇개 = 한줄에 몇개 * 몇줄
$items = $default['de_search_list_mod'] * $default['de_search_list_row'];
// 페이지가 없으면 첫 페이지 (1 페이지)
if ($page == "") $page = 1;
// 시작 레코드 구함
$from_record = ($page - 1) * $items;

// 검색된 내용이 몇행인지를 얻는다
$sql = " select COUNT(*) as cnt $sql_common $sql_where ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$total_page  = ceil($total_count / $items); // 전체 페이지 계산

if ($is_admin) {
    echo '<div class="sit_admin"><a href="'.G5_ADMIN_URL.'/shop_admin/configform.php#anc_scf_etc'.'" class="btn_admin">검색 설정</a></div>';
}
?>

<!-- 검색 시작 { -->
<div id="ssch">

    <!-- 상세검색 항목 시작 { -->
    <div id="ssch_frm">
        <form name="frmdetailsearch">
        <input type="hidden" name="qsort" id="qsort" value="<?php echo $qsort ?>">
        <input type="hidden" name="qorder" id="qorder" value="<?php echo $qorder ?>">
        <input type="hidden" name="qcaid" id="qcaid" value="<?php echo $qcaid ?>">
        <div>
            <strong>검색범위</strong>
            <input type="checkbox" name="qname" id="ssch_qname" class="frm_input" <?php echo isset($qname)?'checked="checked"':'';?>> <label for="ssch_qname">상품명</label>
            <input type="checkbox" name="qexplan" id="ssch_qexplan" class="frm_input" <?php echo isset($qexplan)?'checked="checked"':'';?>> <label for="ssch_qexplan">상품설명</label>
            <input type="checkbox" name="qid" id="ssch_qid" class="frm_input" <?php echo isset($qid)?'checked="checked"':'';?>> <label for="ssch_qid">상품코드</label>
        </div>
        <div>
            <strong>상품가격 (원)</strong>
            <label for="ssch_qfrom" class="sound_only">최소 가격</label>
            <input type="text" name="qfrom" value="<?php echo $qfrom; ?>" id="ssch_qfrom" class="frm_input" size="10"> ~
            <label for="ssch_qto" class="sound_only">최대 가격</label>
            <input type="text" name="qto" value="<?php echo $qto; ?>" id="ssch_qto" class="frm_input" size="10"> 까지
        </div>
        <div>
            <label for="ssch_q" class="ssch_lbl">검색어</label>
            <input type="text" name="q" value="<?php echo $q; ?>" id="ssch_q" class="frm_input" size="40" maxlength="30">
            <input type="submit" value="검색" class="btn_submit">
        </div>
        <p>
            상세검색을 선택하지 않거나, 상품가격을 입력하지 않으면 전체에서 검색합니다.<br>
            검색어는 최대 30글자까지, 여러개의 검색어를 공백으로 구분하여 입력 할수 있습니다.
        </p>
        </form>

        <ul id="ssch_sort">
            <li><a href="#" class="btn01" onclick="set_sort('it_sum_qty', 'desc'); return false;">판매많은순</a></li>
            <li><a href="#" class="btn01" onclick="set_sort('it_price', 'asc'); return false;">낮은가격순</a></li>
            <li><a href="#" class="btn01" onclick="set_sort('it_price', 'desc'); return false;">높은가격순</a></li>
            <li><a href="#" class="btn01" onclick="set_sort('it_use_avg', 'desc'); return false;">평점높은순</a></li>
            <li><a href="#" class="btn01" onclick="set_sort('it_use_cnt', 'desc'); return false;">후기많은순</a></li>
            <li><a href="#" class="btn01" onclick="set_sort('it_update_time', 'desc'); return false;">최근등록순</a></li>
        </ul>

        <div id="ssch_ov">
            검색 결과 <b><?php echo $total_count; ?></b>건
        </div>
    </div>
    <!-- } 상세검색 항목 끝 -->

    <!-- 검색된 분류 시작 { -->
    <div id="ssch_cate">
        <ul>
        <?php
        $sql = " select b.ca_id, b.ca_name, count(*) as cnt $sql_common $sql_where and length(b.ca_id) = 2 group by b.ca_id order by b.ca_id ";
        $result = sql_query($sql);
        $total_cnt = 0;
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            echo "<li><a href=\"#\" onclick=\"set_ca_id('{$row['ca_id']}'); return false;\">{$row['ca_name']} (".$row['cnt'].")</a></li>\n";
            $total_cnt += $row['cnt'];
        }
        echo '<li><a href="#" onclick="set_ca_id(\'\'); return false;">전체분류 <span>('.$total_cnt.')</span></a></li>'.PHP_EOL;
        ?>
        </ul>
    </div>
    <!-- } 검색된 분류 끝 -->

    <!-- 검색결과 시작 { -->
    <div>
        <?php
        // 리스트 유형별로 출력
        $list_file = G5_SHOP_SKIN_PATH.'/'.$default['de_search_list_skin'];
        if (file_exists($list_file)) {

            $list = new item_list($default['de_search_list_skin'], $default['de_search_list_mod'], $default['de_search_list_row'], $default['de_search_img_width'], $default['de_search_img_height']);
            $list->set_query(" select * $sql_common $sql_where {$order_by} limit $from_record, $items ");
            $list->set_is_page(true);
            $list->set_view('it_img', true);
            $list->set_view('it_id', true);
            $list->set_view('it_name', true);
            $list->set_view('it_basic', true);
            $list->set_view('it_cust_price', false);
            $list->set_view('it_price', true);
            $list->set_view('it_icon', true);
            $list->set_view('sns', true);
            echo $list->run();
        }
        else
        {
            $i = 0;
            $error = '<p class="sct_nofile">'.$list_file.' 파일을 찾을 수 없습니다.<br>관리자에게 알려주시면 감사하겠습니다.</p>';
        }

        if ($i==0)
        {
            echo '<div>'.$error.'</div>';
        }

        $query_string .= 'ca_id='.$ca_id.'&amp;q='.urlencode($q);
        $query_string .='&amp;qsort='.$qsort.'&amp;qorder='.$qorder;
        echo get_paging($config['cf_write_pages'], $page, $total_page, $_SERVER['PHP_SELF'].'?'.$query_string.'&amp;page=');
        ?>
    </div>
    <!-- } 검색결과 끝 -->

</div>
<!-- } 검색 끝 -->

<script>
function set_sort(qsort, qorder)
{
    var f = document.frmdetailsearch;
    f.qsort.value = qsort;
    f.qorder.value = qorder;
    f.submit();
}

function set_ca_id(qcaid)
{
    var f = document.frmdetailsearch;
    f.qcaid.value = qcaid;
    f.submit();
}
</script>

<?php
include_once('./_tail.php');
?>