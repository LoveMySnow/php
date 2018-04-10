<?php
/**
 * Created by PhpStorm.
 * User: chenyuan3
 * Date: 2018/4/10
 * Time: 下午2:32
 */

namespace common;
for ($i = 0; $i < 10000; $i++) {
    $id = IdCreater::intiCreater()->getId();
    echo $id . "\n";
}


class IdCreater
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
    private static $LAST_TIMESTAMP;

    /**
     * 拉链的个数
     *
     * @var int
     */
    private static $ZIPPER_NUM = 0;

    /**
     * IdCreater constructor.
     *
     * @throws \Exception
     */
    private function __construct()
    {
        self::$WORK_ID = self::getWorkId();
        self::$LAST_TIMESTAMP = self::getTimestamp();
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
        if ($timestamp < self::$LAST_TIMESTAMP) {
            return self::DEFAULT_ID;
        }

        $max = pow(2, self::ZIPPER_BIT) - 1;
        if ($timestamp == self::$LAST_TIMESTAMP && $max > self::$ZIPPER_NUM) {
            self::$ZIPPER_NUM = self::$ZIPPER_NUM + 1;
        } else {
            /**
             * 当时间戳发生变化是改变
             */
            self::$LAST_TIMESTAMP = self::getNextTimestamp();
            self::$ZIPPER_NUM = 0;
        }

        return self::makeId();
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

    private static function getNextTimestamp()
    {
        $timestamp = self::getTimestamp();
        while ($timestamp <= self::$LAST_TIMESTAMP) {
            $timestamp = self::getTimestamp();
        }

        return $timestamp;
    }

    /**
     * 制作id
     * @return int
     */
    private static function makeId() {

        //时间戳向左移动的位数
        $timestampLeftMoveBit = self::ZIPPER_BIT + self::WORKER_ID_BIT;
        //服务器id向左移动的位数
        $workerIdLeftMoveBit = self::WORKER_ID_BIT;

        /**
         * 将位移运算转化成可读的数学运算， 方便理解
         */
        $first = (self::$LAST_TIMESTAMP - self::BIRTH_DAY) *  pow(2,$timestampLeftMoveBit);
        $second = self::$WORK_ID * pow(2,$workerIdLeftMoveBit);
        $third = self::$ZIPPER_NUM;

        /**
         * 等价于这个位移预算
         * (self::$LAST_TIMESTAMP - self::BIRTH_DAY) << $timestampLeftMoveBit | self::$WORK_ID << $workerIdLeftMoveBit | self::$ZIPPER_NUM;
         */
        return $first + $second + $third;
    }
}