<?php
include_once('./_common.php');

// 결제등록 완료 체크
if($_POST['tran_cd'] == '' || $_POST['enc_info'] == '' || $_POST['enc_data'] == '')
    alert('결제등록 요청 후 주문해 주십시오.');

// 개인결제 정보
$pp_check = false;
$sql = " select * from {$g5['g5_shop_personalpay_table']} where pp_id = '{$_POST['pp_id']}' and pp_use = '1' ";
$pp = sql_fetch($sql);
if(!$pp['pp_id'])
    alert('개인결제 정보가 존재하지 않습니다.');

if($pp['pp_tno'])
    alert('이미 결제하신 개인결제 내역입니다.');

$hash_data = md5($_POST['pp_id'].$_POST['good_mny'].$pp['pp_time']);
if($_POST['pp_id'] != get_session('ss_personalpay_id') || $hash_data != get_session('ss_personalpay_hash'))
    die('개인결제 정보가 올바르지 않습니다.');

if ($pp_settle_case == "계좌이체")
{
    include G5_MSHOP_PATH.'/kcp/pp_ax_hub.php';

    $pp_tno             = $tno;
    $pp_receipt_price   = $amount;
    $pp_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $pp_deposit_name    = $pp_name;
    $bank_name          = iconv("cp949", "utf8", $bank_name);
    $pp_bank_account    = $bank_name;
    $pg_price           = $amount;
}
else if ($pp_settle_case == "가상계좌")
{
    include G5_MSHOP_PATH.'/kcp/pp_ax_hub.php';

    $pp_tno             = $tno;
    $pp_receipt_price   = 0;
    $bankname           = iconv("cp949", "utf8", $bankname);
    $depositor          = iconv("cp949", "utf8", $depositor);
    $pp_bank_account    = $bankname.' '.$account.' '.$depositor;
    $pp_deposit_name    = $depositor;
    $pg_price           = $amount;
}
else if ($pp_settle_case == "휴대폰")
{
    include G5_MSHOP_PATH.'/kcp/pp_ax_hub.php';

    $pp_tno             = $tno;
    $pp_receipt_price   = $amount;
    $pp_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $pp_bank_account    = $commid.' '.$mobile_no;
    $pg_price           = $amount;
}
else if ($pp_settle_case == "신용카드")
{
    include G5_MSHOP_PATH.'/kcp/pp_ax_hub.php';

    $pp_tno             = $tno;
    $pp_receipt_price   = $amount;
    $pp_receipt_time    = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time);
    $card_name          = iconv("cp949", "utf8", $card_name);
    $pp_bank_account    = $card_name;
    $pg_price           = $amount;
}
else
{
    die("od_settle_case Error!!!");
}

// 주문금액과 결제금액이 일치하는지 체크
if((int)$pp['pp_price'] !== (int)$pg_price) {
    $cancel_msg = '결제금액 불일치';
    include G5_MSHOP_PATH.'/kcp/pp_ax_hub_cancel.php'; // 결제취소처리

    die("Receipt Amount Error");
}

if ($is_member)
    $od_pwd = $member['mb_password'];
else
    $od_pwd = sql_password($_POST['od_pwd']);

// 결제정보 입력
$sql = " update {$g5['g5_shop_personalpay_table']}
            set pp_tno              = '$pp_tno',
                pp_app_no           = '$app_no',
                pp_receipt_price    = '$pp_receipt_price',
                pp_settle_case      = '$pp_settle_case',
                pp_bank_account     = '$pp_bank_account',
                pp_deposit_name     = '$pp_deposit_name',
                pp_receipt_time     = '$pp_receipt_time',
                pp_receipt_ip       = '{$_SERVER['REMOTE_ADDR']}'
            where pp_id = '{$pp['pp_id']}' ";
$result = sql_query($sql, false);

// 결제정보 입력 오류시 kcp 결제 취소
if(!$result) {
    if($tno) {
        $cancel_msg = '결제정보 입력 오류';
        include G5_MSHOP_PATH.'/kcp/pp_ax_hub_cancel.php'; // 결제취소처리
    }

    die("<p>$sql<p>" . mysql_errno() . " : " .  mysql_error() . "<p>error file : {$_SERVER['PHP_SELF']}");
}

// 주문번호가 있으면 결제정보 반영
if($pp_receipt_price > 0 && $pp['pp_id'] && $pp['od_id']) {
    $od_escrow = 0;
    if($escw_yn == 'Y')
        $od_escrow = 1;

    $sql = " update {$g5['g5_shop_order_table']}
                set od_receipt_price    = od_receipt_price + '$pp_receipt_price',
                    od_receipt_time     = '$pp_receipt_time',
                    od_tno              = '$pp_tno',
                    od_app_no           = '$app_no',
                    od_escrow           = '$od_escrow',
                    od_settle_case      = '$pp_settle_case',
                    od_deposit_name     = '$pp_deposit_name',
                    od_bank_account     = '$pp_bank_account',
                    od_shop_memo = concat(od_shop_memo, \"\\n개인결제 ".$pp['pp_id']." 로 결제완료 - ".$pp_receipt_time."\")
                where od_id = '{$pp['od_id']}' ";
    $result = sql_query($sql, false);

    // 결제정보 입력 오류시 kcp 결제 취소
    if(!$result) {
        if($tno) {
            $cancel_msg = '결제정보 입력 오류';
            include G5_MSHOP_PATH.'/kcp/pp_ax_hub_cancel.php'; // 결제취소처리
        }

        die("<p>$sql<p>" . mysql_errno() . " : " .  mysql_error() . "<p>error file : {$_SERVER['PHP_SELF']}");
    }

    // 미수금 정보 업데이트
    $info = get_order_info($pp['od_id']);

    $sql = " update {$g5['g5_shop_order_table']}
                set od_misu     = '{$info['od_misu']}' ";
    if($info['od_misu'] == 0)
        $sql .= " , od_status = '입금' ";
    $sql .= " where od_id = '{$pp['od_id']}' ";
    sql_query($sql, FALSE);

    // 장바구니 상태변경
    if($info['od_misu'] == 0) {
        $sql = " update {$g5['g5_shop_cart_table']}
                    set ct_status = '입금'
                    where od_id = '{$pp['od_id']}' ";
        sql_query($sql, FALSE);
    }
}

// 개인결제번호제거
set_session('ss_personalpay_id', '');
set_session('ss_personalpay_hash', '');

$uid = md5($pp['pp_id'].$pp['pp_time'].$_SERVER['REMOTE_ADDR']);
set_session('ss_personalpay_uid', $uid);

goto_url(G5_SHOP_URL.'/personalpayresult.php?pp_id='.$pp['pp_id'].'&amp;uid='.$uid);
?>
