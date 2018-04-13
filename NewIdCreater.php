<?php
/**
 * Created by PhpStorm.
 * User: chenyuan3
 * Date: 2018/4/10
 * Time: 下午2:32
 */

namespace common;
ini_set('display_errors', 1);            //错误信息
ini_set('display_startup_errors', 1);    //php启动错误信息
error_reporting(-1);

use lib\memcache\TqtMemcache;

class NewIdCreater
{
    /**
     * id create 的诞生时间 用于 生成唯一 id
     *
     * @var int
     */
    const BIRTH_DAY = 1523342555743;

    /**
     * 服务器的编码所占字节 （1023）
     *
     * @var int
     */
    const WORKER_ID_BIT = 10;

    /**
     * 毫秒内生成重复id时解决碰撞的拉链 字节 （4095）
     *
     * @var int
     */
    const ZIPPER_BIT = 12;

    /**
     * 程序出现异常返回这个id
     */
    const DEFAULT_ID = 99999999999999;

    /**
     * id生成器实例
     *
     * @var \common\IdCreater
     */
    private static $CREATER;

    /**
     * 服务器id
     *
     * @var int
     */
    private static $WORK_ID;

    /**
     * 最近一次生成id的时间戳
     *
     * @var int
     */
    private $lastTimestamp;

    /**
     * 拉链的个数
     *
     * @var int
     */
    private $zipperNum = 0;

    const LAST_TIME_KEY = "test_time";

    const ZIPPER_KEY = "test_zipper";

    /**
     * IdCreater constructor.
     *
     * @throws \Exception
     */
    private function __construct()
    {
        self::$WORK_ID = self::getWorkId();
        self::initZipperNum();
        $this->lastTimestamp = $this->getLastTimestamp();
    }

    public static function intiCreater()
    {
        if (empty(self::$CREATER)) {
            self::$CREATER = new self();
        }

        return self::$CREATER;
    }

    /**
     * 生成id
     *
     * @return int
     */
    public function getId()
    {
        $timestamp = self::getTimestamp();
        if ($timestamp < $this->lastTimestamp) {
            return self::DEFAULT_ID;
        }

        $max = pow(2, self::ZIPPER_BIT) - 1;
        $zipNum = self::incrementZipperNumber();
        $this->zipperNum = $zipNum % $max;

        if ($this->zipperNum == 0) {
            $this->lastTimestamp = $this->getNextTimestamp();
            $this->setLastTimestamp($this->lastTimestamp);
        }

//        if ($zipNum <= $max) {
//            $this->zipperNum = $zipNum;
//        } else {
//            /**
//             * 当时间戳发生变化是改变
//             */
//            $this->lastTimestamp = $this->getNextTimestamp();
//            $this->setLastTimestamp($this->lastTimestamp);
//
//            $this->zipperNum = $zipNum % $max;
//            self::setZipperNumber($zipNum % $max);
//        }
        return $this->makeId();
    }

    /**
     * @return int
     */
    private static function getWorkId()
    {
//        if ($id > (pow(2, self::WORKER_ID_BIT) - 1)) {
//        }

        return 1;
    }

    private static function getTimestamp()
    {
        return intval(microtime(true) * 1000);
    }

    private function getNextTimestamp()
    {
        $timestamp = self::getTimestamp();
        while ($timestamp <= $this->lastTimestamp) {
            $timestamp = self::getTimestamp();
        }

        return $timestamp;
    }

    private function getLastTimestamp()
    {
        $last = TqtMemcache::getMemcache()->get(self::LAST_TIME_KEY);
        if (empty($last)) {
            $last = self::getTimestamp();
            $this->setLastTimestamp($last);
        }

        return $last;
    }

    private function setLastTimestamp($timestamp)
    {
        return TqtMemcache::getMemcache()->set(self::LAST_TIME_KEY, $timestamp, 3600);
    }

    private static function incrementZipperNumber()
    {
        return TqtMemcache::getMemcache()->increment(self::ZIPPER_KEY);
    }

    private static function setZipperNumber($num)
    {
        return TqtMemcache::getMemcache()->set(self::ZIPPER_KEY, $num, 3600);
    }

    private static function initZipperNum()
    {
        $zipNum = TqtMemcache::getMemcache()->get(self::ZIPPER_KEY);
        if ($zipNum == false) {
            self::setZipperNumber(0);
        }
    }

    /**
     * 制作id
     *
     * @return int
     */
    private function makeId()
    {

        //时间戳向左移动的位数
        $timestampLeftMoveBit = self::ZIPPER_BIT + self::WORKER_ID_BIT;
        //服务器id向左移动的位数
        $workerIdLeftMoveBit = self::WORKER_ID_BIT;

        /**
         * 将位移运算转化成可读的数学运算， 方便理解
         */
        $first = ($this->lastTimestamp - self::BIRTH_DAY) * pow(2, $timestampLeftMoveBit);
        $second = self::$WORK_ID * pow(2, $workerIdLeftMoveBit);
        $third = $this->zipperNum;

        /**
         * 等价于这个位移预算
         * (self::$LAST_TIMESTAMP - self::BIRTH_DAY) << $timestampLeftMoveBit | self::$WORK_ID << $workerIdLeftMoveBit | self::$ZIPPER_NUM;
         */
        return $first + $second + $third;
    }
}