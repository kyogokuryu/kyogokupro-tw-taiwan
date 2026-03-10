<?php
/**
 * Created by SYSTEM_KD
 * Date: 2019-10-18
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

interface CsvMultiControllerInterface
{

    /**
     * メイン画面
     *
     * @Route("/%eccube_admin_route%/xxxx", name="xxxx")
     * @Template("@XXXX/admin/csv.twig")
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request);

    /**
     * 雛形ダウンロード
     *
     * @Route("/%eccube_admin_route%/xxxx", name="xxxx")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function templateDownload(Request $request);

    /**
     * 雛形＋データダウンロード
     *
     * @Route("/%eccube_admin_route%/xxxx", name="xxxx")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function templateDataDownload(Request $request);
}
