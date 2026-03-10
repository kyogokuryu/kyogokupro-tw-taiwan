<?php

namespace Plugin\ECCUBE4LineIntegration;

use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{

    /**
     * @var string コピー元リソースディレクトリ
     */
    private $origin;

    /**
     * @var string コピー先リソースディレクトリ
     */
    private $target;

    public function __construct()
    {
        // リネーム元のディレクトリ
        $this->origin = __DIR__ . '/../../../html/plugin/ECCUBE4LineIntegration';
        // リネーム後のディレクトリ
        $this->target = __DIR__ . '/../../../html/plugin/line_integration';
    }

    public function install(array $meta, ContainerInterface $container)
    {
        $this->renameAssets();
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->removeAssets();
    }

    public function enable(array $meta, ContainerInterface $container)
    {
    }

    public function disable(array $meta, ContainerInterface $container)
    {
    }

    public function update(array $meta, ContainerInterface $container)
    {
    }

    /**
     * インストール時にコピーした画像ファイルなどのディレクトリ名をリネーム
     */
    private function renameAssets()
    {
        $file = new Filesystem();
        $file->rename($this->origin, $this->target);
    }

    /**
     * コピーした画像ファイルなどを削除
     */
    private function removeAssets()
    {
        $file = new Filesystem();
        $file->remove($this->target);
    }
}
