<?php
/**
 * Author: Yun
 * Created: Atom.
 * Date: 2017/3/30
 * 日志的API类
 */
namespace Api\Controller;

use Home\Model\LandLogModel;
use Home\Model\SweepLogModel;
use Home\Model\TradeInfoModel;
use Home\Model\ProduceggsLogModel;
use Common\Controller\HomeBaseController;

class LogController extends HomeBaseController
{
    /**
     * [getSell 获取当前用户的卖出列表]
     * @return [json] [AJAX调用的数据]
     */
    public function getSell()
    {
        $log = new TradeInfoModel();
        echo json_encode($log->relog(session('user_id'), 1), 320);
    }

    /**
     * [getSell 获取当前用户的买入列表]
     * @return [json] [AJAX调用的数据]
     */
    public function getBuy()
    {
        $log = new TradeInfoModel();
        echo json_encode($log->relog(session('user_id'), 0), 320);
    }
    /**
     * [getProc 获取当前用户的生产列表]
     * @return [json] [AJAX调用的数据]
     */
    public function getProc()
    {
        $log = new ProduceggsLogModel();
        echo json_encode($log->getProc(session('user_id')), 320);
    }
    /**
     * [getSell 获取当前用户的打扫列表]
     * @return [json] [AJAX调用的数据]
     */
    public function getSweep()
    {
        $log = new SweepLogModel();
        echo json_encode($log->getSweepLog(session('user_id')), 320);
    }
    /**
     * [getEggs 获取当前用户的孵化日志]
     * @return [json] [列表及分页信息]
     */
    public function getEggs()
    {
        $log = new LandLogModel();
        echo json_encode($log->relog(session('user_id'), 0), 320);
    }
    /**
     * [getFishs 获取当前用户的增养日志]
     * @return [json] [列表及分页信息]
     */
    public function getFishs()
    {
        $log = new LandLogModel();
        echo json_encode($log->relog(session('user_id'), 1), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
    }




























    /**
     * [getThree 获取当前用户三日的生产和打扫数据]
     * @return [json] [AJAX调用的数据]
     */
    public function getThree()
    {
        $three_proc = new ProduceggsLogModel();
        $three_sweep = new SweepLogModel();
        $sweep = $three_sweep->getThreeSweep(session('user_id'));
        $proc  = $three_proc->getThreeProc(session('user_id'));
        echo json_encode(['proc'=>$proc, 'sweep'=>$sweep], 320);
    }


}
