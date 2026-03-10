<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/26
 */

namespace Plugin\KokokaraSelect\Service;


use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Repository\KsSelectItemGroupRepository;

class KsSelectItemGroupService
{

    use ConfigHelperTrait;

    /** @var KsSelectItemGroupRepository */
    protected $ksSelectItemGroupRepository;

    public function __construct(
        KsSelectItemGroupRepository $ksSelectItemGroupRepository
    )
    {
        $this->ksSelectItemGroupRepository = $ksSelectItemGroupRepository;
    }

    /**
     * 表示用グループ名取得
     *
     * @param KsSelectItemGroup $ksSelectItemGroup
     * @param string $index
     * @return string|null
     */
    public function getViewGroupName(KsSelectItemGroup $ksSelectItemGroup, $index = "")
    {
        $groupName = $ksSelectItemGroup->getGroupName();
        if (empty($groupName)) {
            $groupName = $this->getDefaultGroupName() . $index;
        }

        return $groupName;
    }
}
