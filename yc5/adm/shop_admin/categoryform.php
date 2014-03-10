<?php
$sub_menu = '400200';
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "w");

$category_path = G5_DATA_PATH."/category";

$sql_common = " from {$g5['g5_shop_category_table']} ";
if ($is_admin != 'super')
    $sql_common .= " where ca_mb_id = '{$member['mb_id']}' ";

if ($w == "")
{
    if ($is_admin != 'super' && !$ca_id)
        alert("최고관리자만 1단계 분류를 추가할 수 있습니다.");

    $len = strlen($ca_id);
    if ($len == 10)
        alert("분류를 더 이상 추가할 수 없습니다.\\n\\n5단계 분류까지만 가능합니다.");

    $len2 = $len + 1;

    $sql = " select MAX(SUBSTRING(ca_id,$len2,2)) as max_subid from {$g5['g5_shop_category_table']}
              where SUBSTRING(ca_id,1,$len) = '$ca_id' ";
    $row = sql_fetch($sql);

    $subid = base_convert($row['max_subid'], 36, 10);
    $subid += 36;
    if ($subid >= 36 * 36)
    {
        //alert("분류를 더 이상 추가할 수 없습니다.");
        // 빈상태로
        $subid = "  ";
    }
    $subid = base_convert($subid, 10, 36);
    $subid = substr("00" . $subid, -2);
    $subid = $ca_id . $subid;

    $sublen = strlen($subid);

    if ($ca_id) // 2단계이상 분류
    {
        $sql = " select * from {$g5['g5_shop_category_table']} where ca_id = '$ca_id' ";
        $ca = sql_fetch($sql);
        $html_title = $ca['ca_name'] . " 하위분류추가";
        $ca['ca_name'] = "";
    }
    else // 1단계 분류
    {
        $html_title = "1단계분류추가";
        $ca['ca_use'] = 1;
        $ca['ca_explan_html'] = 1;
        $ca['ca_img_width']  = $default['de_simg_width'];
        $ca['ca_img_height'] = $default['de_simg_height'];
        $ca['ca_mobile_img_width']  = $default['de_simg_width'];
        $ca['ca_mobile_img_height'] = $default['de_simg_height'];
        $ca['ca_list_mod'] = 3;
        $ca['ca_list_row'] = 5;
        $ca['ca_mobile_list_mod'] = 10;
        $ca['ca_stock_qty'] = 99999;
    }
    $ca['ca_skin'] = "list.10.skin.php";
    $ca['ca_mobile_skin'] = "list.10.skin.php";
}
else if ($w == "u")
{
    $sql = " select * from {$g5['g5_shop_category_table']} where ca_id = '$ca_id' ";
    $ca = sql_fetch($sql);
    if (!$ca['ca_id'])
        alert("자료가 없습니다.");

    $html_title = $ca['ca_name'] . " 수정";
    $ca['ca_name'] = get_text($ca['ca_name']);
}

$qstr = 'page='.$page.'&amp;sort1='.$sort1.'&amp;sort2='.$sort2;

$g5['title'] = $html_title;
include_once (G5_ADMIN_PATH.'/admin.head.php');

$pg_anchor ='<ul class="anchor">
<li><a href="#anc_scatefrm_basic">필수입력</a></li>
<li><a href="#anc_scatefrm_optional">선택입력</a></li>
<li><a href="#anc_scatefrm_extra">여분필드</a></li>';
if ($w == 'u') $pg_anchor .= '<li><a href="#frm_etc">기타설정</a></li>';
$pg_anchor .= '</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit" accesskey="s">
    <a href="./categorylist.php?'.$qstr.'">목록</a>
</div>';

// 쿠폰 적용 불가 설정 필드 추가
if(!sql_query(" select ca_nocoupon from {$g5['g5_shop_category_table']} limit 1 ", false)) {
    sql_query(" ALTER TABLE `{$g5['g5_shop_category_table']}`
                    ADD `ca_nocoupon` tinyint(4) NOT NULL DEFAULT '0' AFTER `ca_adult_use` ", true);
}
?>

<form name="fcategoryform" action="./categoryformupdate.php" onsubmit="return fcategoryformcheck(this);" method="post" enctype="multipart/form-data">

<input type="hidden" name="codedup"  value="<?php echo $default['de_code_dup_use']; ?>">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="sort1" value="<?php echo $sort1; ?>">
<input type="hidden" name="sort2" value="<?php echo $sort2; ?>">
<input type="hidden" name="ca_explan_html" value="<?php echo $ca['ca_explan_html']; ?>">

<section id="anc_scatefrm_basic">
    <h2 class="h2_frm">필수입력</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>분류 추가 필수입력</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="ca_id">분류코드</label></th>
            <td>
            <?php if ($w == "") { ?>
                <?php echo help("자동으로 보여지는 분류코드를 사용하시길 권해드리지만 직접 입력한 값으로도 사용할 수 있습니다.\n분류코드는 나중에 수정이 되지 않으므로 신중하게 결정하여 사용하십시오.\n\n분류코드는 2자리씩 10자리를 사용하여 5단계를 표현할 수 있습니다.\n0~z까지 입력이 가능하며 한 분류당 최대 1296가지를 표현할 수 있습니다.\n그러므로 총 3656158440062976가지의 분류를 사용할 수 있습니다."); ?>
                <input type="text" name="ca_id" value="<?php echo $subid; ?>" id="ca_id" class="frm_input" size="<?php echo $sublen; ?>" maxlength="<?php echo $sublen; ?>">
                <!-- <?php if ($default['de_code_dup_use']) { ?><a href="javascript:;" onclick="codedupcheck(document.getElementById('ca_id').value)">코드 중복검사</a><?php } ?> -->
            <?php } else { ?>
                <input type="hidden" name="ca_id" value="<?php echo $ca['ca_id']; ?>">
                <span class="frm_ca_id"><?php echo $ca['ca_id']; ?></span>
                <a href="<?php echo G5_SHOP_URL; ?>/list.php?ca_id=<?php echo $ca_id; ?>" class="btn_frmline">미리보기</a>
                <a href="./categoryform.php?ca_id=<?php echo $ca_id; ?>&amp;<?php echo $qstr; ?>" class="btn_frmline">하위분류 추가</a>
                <a href="./itemlist.php?sca=<?php echo $ca['ca_id']; ?>" class="btn_frmline">상품리스트</a>
            <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_name">분류명</label></th>
            <td><input type="text" name="ca_name" value="<?php echo $ca['ca_name']; ?>" id="ca_name" size="38" required class="required frm_input"></td>
        </tr>
        <tr>
            <th scope="row"><?php if ($is_admin == 'super') { ?><label for="ca_mb_id"><?php } ?>관리 회원아이디<?php if ($is_admin == 'super') { ?></label><?php } ?></th>
            <td>
                <?php if ($is_admin == 'super') { ?>
                    <input type="text" name="ca_mb_id" value="<?php echo $ca['ca_mb_id']; ?>" id="ca_mb_id" class="frm_input" maxlength="20">
                <?php } else { ?>
                    <input type="hidden" name="ca_mb_id" value="<?php echo $ca['ca_mb_id']; ?>">
                    <?php echo $ca['ca_mb_id']; ?>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row">본인확인 체크</th>
            <td>
                <input type="radio" name="ca_cert_use" value="1" id="ca_cert_use_yes" <?php if($ca['ca_cert_use']) echo 'checked="checked"'; ?>>
                <label for="ca_cert_use_yes">사용함</label>
                <input type="radio" name="ca_cert_use" value="0" id="ca_cert_use_no" <?php if(!$ca['ca_cert_use']) echo 'checked="checked"'; ?>>
                <label for="ca_cert_use_no">사용안함</label>
            </td>
        </tr>
        <tr>
            <th scope="row">성인인증 체크</th>
            <td>
                <input type="radio" name="ca_adult_use" value="1" id="ca_adult_use_yes" <?php if($ca['ca_adult_use']) echo 'checked="checked"'; ?>>
                <label for="ca_adult_use_yes">사용함</label>
                <input type="radio" name="ca_adult_use" value="0" id="ca_adult_use_no" <?php if(!$ca['ca_adult_use']) echo 'checked="checked"'; ?>>
                <label for="ca_adult_use_no">사용안함</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_skin">출력스킨</label></th>
            <td>
                <?php echo help('기본으로 제공하는 스킨은 '.str_replace(G5_PATH.'/', '', G5_SHOP_SKIN_PATH).'/list.*.skin.php 입니다.'); ?>
                <select id="ca_skin" name="ca_skin">
                    <?php echo get_list_skin_options("^list.[0-9]+\.skin\.php", G5_SHOP_SKIN_PATH, $ca['ca_skin']); ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_img_width">출력이미지 폭</label></th>
            <td>
                <?php echo help("쇼핑몰환경설정 &gt; 이미지(소) 넓이가 기본값으로 설정됩니다.\n".G5_SHOP_URL."/list.php에서 출력되는 이미지의 폭입니다."); ?>
                <input type="text" name="ca_img_width" value="<?php echo $ca['ca_img_width']; ?>" id="ca_img_width" required class="required frm_input" size="5" > 픽셀
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_img_height">출력이미지 높이</label></th>
            <td>
                <?php echo help("쇼핑몰환경설정 &gt; 이미지(소) 높이가 기본값으로 설정됩니다.\n".G5_SHOP_URL."/list.php에서 출력되는 이미지의 높이입니다."); ?>
                <input type="text" name="ca_img_height"  value="<?php echo $ca['ca_img_height']; ?>" id="ca_img_height" required class="required frm_input" size="5" > 픽셀
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_list_mod">1줄당 이미지 수</label></th>
            <td>
                <?php echo help("한 줄에 설정한 값만큼의 상품을 출력하지만 스킨에 따라 한 줄에 하나의 상품만 출력할 수도 있습니다."); ?>
                <input type="text" name="ca_list_mod" size="3" value="<?php echo $ca['ca_list_mod']; ?>" id="ca_list_mod" required class="required frm_input"> 개
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_list_row">이미지 줄 수</label></th>
            <td>
                <?php echo help("한 페이지에 출력할 이미지 줄 수를 설정합니다.\n한 페이지에서 표시하는 상품수는 (1줄당 이미지 수 x 줄 수) 입니다."); ?>
                <input type="text" name="ca_list_row" value='<?php echo $ca['ca_list_row']; ?>' id="ca_list_row" required class="required frm_input" size="3"> 줄
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_mobile_skin">모바일 출력스킨</label></th>
            <td>
                <?php echo help('기본으로 제공하는 스킨은 '.str_replace(G5_PATH.'/', '', G5_MSHOP_SKIN_PATH).'/list.*.skin.php 입니다.'); ?>
                <select id="ca_mobile_skin" name="ca_mobile_skin">
                    <?php echo get_list_skin_options("^list.[0-9]+\.skin\.php", G5_MSHOP_SKIN_PATH, $ca['ca_mobile_skin']); ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_mobile_img_width">모바일 출력이미지 폭</label></th>
            <td>
                <?php echo help("쇼핑몰환경설정 &gt; 이미지(소) 넓이가 기본값으로 설정됩니다.\n".G5_SHOP_URL."/list.php에서 출력되는 이미지의 폭입니다."); ?>
                <input type="text" name="ca_mobile_img_width" value="<?php echo $ca['ca_mobile_img_width']; ?>" id="ca_mobile_img_width" required class="required frm_input" size="5" > 픽셀
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_mobile_img_height">모바일 출력이미지 높이</label></th>
            <td>
                <?php echo help("쇼핑몰환경설정 &gt; 이미지(소) 높이가 기본값으로 설정됩니다.\n".G5_SHOP_URL."/list.php에서 출력되는 이미지의 높이입니다."); ?>
                <input type="text" name="ca_mobile_img_height"  value="<?php echo $ca['ca_mobile_img_height']; ?>" id="ca_mobile_img_height" required class="required frm_input" size="5" > 픽셀
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_mobile_list_mod">모바일 이미지 수</label></th>
            <td>
                <?php echo help("한 페이지에 출력할 이미지 수를 설정합니다."); ?>
                <input type="text" name="ca_mobile_list_mod" value='<?php echo $ca['ca_mobile_list_mod']; ?>' id="ca_mobile_list_mod" required class="required frm_input" size="3">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_stock_qty">재고수량</label></th>
            <td>
                <?php echo help("상품의 기본재고 수량을 설정합니다.\n재고를 사용하지 않는다면 숫자를 크게 입력하여 주십시오. 예) 999999"); ?>
                <input type="text" name="ca_stock_qty" size="10" value="<?php echo $ca['ca_stock_qty']; ?>" id="ca_stock_qty" class="frm_input"> 개
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_sell_email">판매자 E-mail</label></th>
            <td>
                <?php echo help("운영자와 판매자가 다른 경우에 사용합니다.\n이 분류에 속한 상품을 등록할 경우에 기본값으로 입력됩니다."); ?>
                <input type="text" name="ca_sell_email" size="40" value="<?php echo $ca['ca_sell_email']; ?>" id="ca_sell_email" class="frm_input">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_use">판매가능</label></th>
            <td>
                <?php echo help("재고가 없거나 일시적으로 판매를 중단하시려면 체크 해제하십시오.\n체크 해제하시면 상품 출력을 하지 않으며, 주문도 받지 않습니다."); ?>
                <input type="checkbox" name="ca_use" <?php echo ($ca['ca_use']) ? "checked" : ""; ?> value="1" id="ca_use">
                예
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_nocoupon">쿠폰적용안함</label></th>
            <td>
                <?php echo help("설정에 체크하시면 쿠폰생성 때 분류 검색 결과에 노출되지 않습니다."); ?>
                <input type="checkbox" name="ca_nocoupon" <?php echo ($ca['ca_nocoupon']) ? "checked" : ""; ?> value="1" id="ca_nocoupon">
                예
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_scatefrm_optional">
    <h2 class="h2_frm">선택 입력</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>분류 추가 선택입력</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="ca_include_head">상단파일경로</label></th>
            <td>
                <?php echo help("입력하지 않으면 기본 상단 파일을 사용합니다.<br>상단 내용과 달리 PHP 코드를 사용할 수 있습니다."); ?>
                <input type="text" name="ca_include_head" value="<?php echo $ca['ca_include_head']; ?>" id="ca_include_head" class="frm_input" size="60">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ca_include_tail">하단 파일 경로</label></th>
            <td>
                <?php echo help("입력하지 않으면 기본 하단 파일을 사용합니다.<br>하단 내용과 달리 PHP 코드를 사용할 수 있습니다."); ?>
                <input type="text" name="ca_include_tail" value="<?php echo $ca['ca_include_tail']; ?>" id="ca_include_tail" class="frm_input" size="60">
            </td>
        </tr>
        <tr>
            <th scope="row">상단내용</th>
            <td>
                <?php echo help("상품리스트 페이지 상단에 출력하는 HTML 내용입니다."); ?>
                <?php echo editor_html('ca_head_html', $ca['ca_head_html']); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">하단내용</th>
            <td>
                <?php echo help("상품리스트 페이지 하단에 출력하는 HTML 내용입니다.", -150); ?>
                <?php echo editor_html('ca_tail_html', $ca['ca_tail_html']); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">모바일 상단내용</th>
            <td>
                <?php echo help("상품리스트 페이지 상단에 출력하는 HTML 내용입니다."); ?>
                <?php echo editor_html('ca_mobile_head_html', $ca['ca_mobile_head_html']); ?>
            </td>
        </tr>
        <tr>
            <th scope="row">모바일 하단내용</th>
            <td>
                <?php echo help("상품리스트 페이지 하단에 출력하는 HTML 내용입니다.", -150); ?>
                <?php echo editor_html('ca_mobile_tail_html', $ca['ca_mobile_tail_html']); ?>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_scatefrm_extra">
    <h2>여분필드 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <colgroup>
            <col class="grid_3">
            <col>
        </colgroup>
        <tbody>
        <?php for ($i=1; $i<=10; $i++) { ?>
        <tr>
            <th scope="row">여분필드<?php echo $i ?></th>
            <td class="td_extra">
                <label for="ca_<?php echo $i ?>_subj">여분필드 <?php echo $i ?> 제목</label>
                <input type="text" name="ca_<?php echo $i ?>_subj" id="ca_<?php echo $i ?>_subj" value="<?php echo get_text($ca['ca_'.$i.'_subj']) ?>" class="frm_input">
                <label for="ca_<?php echo $i ?>">여분필드 <?php echo $i ?> 값</label>
                <input type="text" name="ca_<?php echo $i ?>" value="<?php echo get_text($ca['ca_'.$i]) ?>" id="ca_<?php echo $i ?>" class="frm_input">
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<?php if ($w == "u") { ?>
<section id="frm_etc">
    <h2 class="h2_frm">기타설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>분류 추가 기타설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">하위분류</th>
            <td>
                <?php echo help("이 분류의 코드가 10 이라면 10 으로 시작하는 하위분류의 설정값을 이 분류와 동일하게 설정합니다.\n<strong>이 작업은 실행 후 복구할 수 없습니다.</strong>"); ?>
                <label for="sub_category">이 분류의 하위분류 설정을, 이 분류와 동일하게 일괄수정</label>
                <input type="checkbox" name="sub_category" value="1" id="sub_category" onclick="if (this.checked) if (confirm('이 분류에 속한 하위 분류의 속성을 똑같이 변경합니다.\n\n이 작업은 되돌릴 방법이 없습니다.\n\n그래도 변경하시겠습니까?')) return ; this.checked = false;">
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>
<?php echo $frm_submit; ?>
<?php } ?>

</form>

<script>
<?php if ($w == 'u') { ?>
$(".banner_or_img").addClass("sit_wimg");
$(function() {
    $(".sit_wimg_view").bind("click", function() {
        var sit_wimg_id = $(this).attr("id").split("_");
        var $img_display = $("#"+sit_wimg_id[1]);

        $img_display.toggle();

        if($img_display.is(":visible")) {
            $(this).text($(this).text().replace("확인", "닫기"));
        } else {
            $(this).text($(this).text().replace("닫기", "확인"));
        }

        var $img = $("#"+sit_wimg_id[1]).children("img");
        var width = $img.width();
        var height = $img.height();
        if(width > 700) {
            var img_width = 700;
            var img_height = Math.round((img_width * height) / width);

            $img.width(img_width).height(img_height);
        }
    });
    $(".sit_wimg_close").bind("click", function() {
        var $img_display = $(this).parents(".banner_or_img");
        var id = $img_display.attr("id");
        $img_display.toggle();
        var $button = $("#ca_"+id+"_view");
        $button.text($button.text().replace("닫기", "확인"));
    });
});
<?php } ?>

function fcategoryformcheck(f)
{
    if (f.w.value == "") {
        var error = "";
        $.ajax({
            url: "./ajax.ca_id.php",
            type: "POST",
            data: {
                "ca_id": f.ca_id.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                error = data.error;
            }
        });

        if (error) {
            alert(error);
            return false;
        }
    }

    <?php echo get_editor_js('ca_head_html'); ?>
    <?php echo get_editor_js('ca_tail_html'); ?>
    <?php echo get_editor_js('ca_mobile_head_html'); ?>
    <?php echo get_editor_js('ca_mobile_tail_html'); ?>

    return true;
}

/*document.fcategoryform.ca_name.focus(); 포커스 해제*/
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>