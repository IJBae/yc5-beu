<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$def_locale = setlocale(LC_CTYPE, 0);
$cancel_msg = iconv("utf-8", "euc-kr", $cancel_msg);
$locale_change = false;
if(preg_match("/utf[\-]?8/i", $def_locale)) {
    setlocale(LC_CTYPE, 'ko_KR.euc-kr');
    $locale_change = true;
}

/* ============================================================================== */
/* =   07. 승인 결과 DB처리 실패시 : 자동취소                                   = */
/* = -------------------------------------------------------------------------- = */
/* =         승인 결과를 DB 작업 하는 과정에서 정상적으로 승인된 건에 대해      = */
/* =         DB 작업을 실패하여 DB update 가 완료되지 않은 경우, 자동으로       = */
/* =         승인 취소 요청을 하는 프로세스가 구성되어 있습니다.                = */
/* =                                                                            = */
/* =         DB 작업이 실패 한 경우, bSucc 라는 변수(String)의 값을 "false"     = */
/* =         로 설정해 주시기 바랍니다. (DB 작업 성공의 경우에는 "false" 이외의 = */
/* =         값을 설정하시면 됩니다.)                                           = */
/* = -------------------------------------------------------------------------- = */

$bSucc = "false"; // DB 작업 실패 또는 금액 불일치의 경우 "false" 로 세팅

/* = -------------------------------------------------------------------------- = */
/* =   07-1. DB 작업 실패일 경우 자동 승인 취소                                 = */
/* = -------------------------------------------------------------------------- = */
if ( $req_tx == "pay" )
{
    if( $res_cd == "0000" )
    {
        if ( $bSucc == "false" )
        {
            $c_PayPlus->mf_clear();

            $tran_cd = "00200000";

            /* ============================================================================== */
            /* =   07-1.자동취소시 에스크로 거래인 경우                                     = */
            /* = -------------------------------------------------------------------------- = */
            // 취소시 사용하는 mod_type
            $bSucc_mod_type = "";

            // 에스크로 가상계좌 건의 경우 가상계좌 발급취소(STE5)
            if ( $escw_yn == "Y" && $use_pay_method == "001000000000" )
            {
                $bSucc_mod_type = "STE5";
            }
            // 에스크로 가상계좌 이외 건은 즉시취소(STE2)
            else if ( $escw_yn == "Y" )
            {
                $bSucc_mod_type = "STE2";
            }
            // 에스크로 거래 건이 아닌 경우(일반건)(STSC)
            else
            {
                $bSucc_mod_type = "STSC";
            }
            /* = -------------------------------------------------------------------------- = */
            /* =   07-1. 자동취소시 에스크로 거래인 경우 처리 END                           = */
            /* = ========================================================================== = */

            $c_PayPlus->mf_set_modx_data( "tno",      $tno                         );  // KCP 원거래 거래번호
            $c_PayPlus->mf_set_modx_data( "mod_type", $bSucc_mod_type              );  // 원거래 변경 요청 종류
            $c_PayPlus->mf_set_modx_data( "mod_ip",   $cust_ip                     );  // 변경 요청자 IP
            $c_PayPlus->mf_set_modx_data( "mod_desc", $cancel_msg );  // 변경 사유

            $c_PayPlus->mf_do_tx( $tno,  $g_conf_home_dir, $g_conf_site_cd,
                                  $g_conf_site_key,  $tran_cd,    "",
                                  $g_conf_gw_url,  $g_conf_gw_port,  "payplus_cli_slib",
                                  $ordr_idxx, $cust_ip, "3" ,
                                  0, 0, $g_conf_key_dir, $g_conf_log_dir);

            $res_cd  = $c_PayPlus->m_res_cd;
            $res_msg = $c_PayPlus->m_res_msg;
        }
    }
} // End of [res_cd = "0000"]
/* ============================================================================== */

if($locale_change)
    setlocale(LC_CTYPE, $def_locale);
?>