<?php

/*
 * Project Name: ダウンロードコンテンツ販売 プラグイン for 4.0
 * Copyright(c) 2019 Kenji Nakanishi. All Rights Reserved.
 *
 * https://www.facebook.com/web.kenji.nakanishi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SheebDlc\Entity;

use Eccube\Annotation\EntityExtension;
use Eccube\Annotation as Eccube;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @ORM\Column(name="sheeb_download_content", type="string", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\TextType",
     *  options={
     *    "required": false,
     *    "label": "sheeb.dlc.admin.product.detail.sheeb_download_content",
     *  }
     * )
     */
    private $sheeb_download_content;

    /**
     * @ORM\Column(name="sheeb_dlc_mime", type="string", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\HiddenType",
     *  options={
     *    "required": false
     *  }
     * )
     */
    private $sheeb_dlc_mime;

    /**
     * @ORM\Column(name="sheeb_dlc_save_url", type="string", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\HiddenType",
     *  options={
     *    "required": false
     *  }
     * )
     */
    private $sheeb_dlc_save_url;

    /**
     * @ORM\Column(name="sheeb_dlc_origin_file_name", type="string", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\HiddenType",
     *  options={
     *    "required": false
     *  }
     * )
     */
    private $sheeb_dlc_origin_file_name;
    
    /**
     * @ORM\Column(name="sheeb_dlc_download_due_days", type="integer", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\IntegerType",
     *  options={
     *    "required": false,
     *    "label": "sheeb.dlc.admin.product.detail.sheeb_dlc_download_due_days",
     *  }
     * )
     */
    private $sheeb_dlc_download_due_days;

    /**
     * @ORM\Column(name="sheeb_dlc_viewing_days", type="integer", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\IntegerType",
     *  options={
     *    "required": false,
     *    "label": "sheeb.dlc.admin.product.detail.sheeb_dlc_viewing_days",
     *  }
     * )
     */
    private $sheeb_dlc_viewing_days;

    /**
     * @ORM\Column(name="sheeb_dlc_downloadable_count", type="integer", nullable=true)
     * @Eccube\FormAppend(
     *  auto_render=false,
     *  type="\Symfony\Component\Form\Extension\Core\Type\IntegerType",
     *  options={
     *    "required": false,
     *    "label": "sheeb.dlc.admin.product.detail.sheeb_dlc_downloadable_count",
     *  }
     * )
     */
    private $sheeb_dlc_downloadable_count;

    /**
     * @return mixed
     */
    public function getSheebDlcDownloadableCount()
    {
        return $this->sheeb_dlc_downloadable_count;
    }

    /**
     * @param $sheeb_dlc_downloadable_count
     * @return ProductTrait
     */
    public function setSheebDlcDownloadableCount($sheeb_dlc_downloadable_count): self
    {
        $this->sheeb_dlc_downloadable_count = $sheeb_dlc_downloadable_count;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSheebDlcDownloadDueDays()
    {
        return $this->sheeb_dlc_download_due_days;
    }

    /**
     * @param $sheeb_dlc_download_due_days
     * @return ProductTrait
     */
    public function setSheebDlcDownloadDueDays($sheeb_dlc_download_due_days): self
    {
        $this->sheeb_dlc_download_due_days = $sheeb_dlc_download_due_days;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSheebDlcMime()
    {
        return $this->sheeb_dlc_mime;
    }

    /**
     * @param $sheeb_dlc_mime
     * @return ProductTrait
     */
    public function setSheebDlcMime($sheeb_dlc_mime): self
    {
        $this->sheeb_dlc_mime = $sheeb_dlc_mime;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSheebDlcViewingDays()
    {
        return $this->sheeb_dlc_viewing_days;
    }

    /**
     * @param $sheeb_dlc_viewing_days
     * @return ProductTrait
     */
    public function setSheebDlcViewingDays($sheeb_dlc_viewing_days): self
    {
        $this->sheeb_dlc_viewing_days = $sheeb_dlc_viewing_days;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSheebDownloadContent()
    {
        return $this->sheeb_download_content;
    }

    /**
     * @param $sheeb_download_content
     * @return ProductTrait
     */
    public function setSheebDownloadContent($sheeb_download_content): self
    {
        $this->sheeb_download_content = $sheeb_download_content;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSheebDlcSaveUrl()
    {
        return $this->sheeb_dlc_save_url;
    }

    /**
     * @param $sheeb_dlc_save_url
     * @return ProductTrait
     */
    public function setSheebDlcSaveUrl($sheeb_dlc_save_url): self
    {
        $this->sheeb_dlc_save_url = $sheeb_dlc_save_url;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSheebDlcOriginFileName()
    {
        return $this->sheeb_dlc_origin_file_name;
    }

    /**
     * @param $sheeb_dlc_origin_file_name
     * @return $this
     */
    public function setSheebDlcOriginFileName($sheeb_dlc_origin_file_name)
    {
        $this->sheeb_dlc_origin_file_name = $sheeb_dlc_origin_file_name;
        return $this;
    }
}
