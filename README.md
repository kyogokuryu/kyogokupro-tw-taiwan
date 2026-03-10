# Kyogoku Professional Taiwan (tw.kyogokupro.com)

台湾向け Kyogoku Professional EC サイト。EC-CUBE 4.x ベースに独自機能を追加。

## 構成

| ディレクトリ | 説明 |
|---|---|
| `app/` | EC-CUBE カスタマイズ（Entity, Form, Controller） |
| `src/` | EC-CUBE コア + GMO 決済モジュール |
| `html/template/` | カスタムテンプレート（Twig） |
| `html/user_data/` | ユーザーデータ |
| `feed/` | 動画フィード機能（EC-CUBE 独立） |
| `personalcolor_diagnosis/` | パーソナルカラー診断機能 |
| `images/` | サイト画像素材 |

## 除外ディレクトリ（.gitignore）

| ディレクトリ | 理由 |
|---|---|
| `vendor/` | Composer で復元（`composer install`） |
| `var/` | キャッシュ・ログ・セッション |
| `html/upload/` | 商品画像（サーバー管理） |
| `note/` | WordPress ブログ（別管理） |
| `.env` | 認証情報 |

## デプロイ

`main` ブランチへの push で GitHub Actions が自動デプロイを実行。

### 必要な Secrets

| Secret | 説明 |
|---|---|
| `SSH_PRIVATE_KEY` | Xserver SSH 秘密鍵 |
| `SSH_PASSPHRASE` | SSH パスフレーズ |

### デプロイ先

```
xs679489@xs679489.xsrv.jp:/home/xs679489/kyogokupro.com/public_html/tw/
```

## セットアップ

```bash
# 依存パッケージのインストール
composer install --no-dev --optimize-autoloader

# .env の設定
cp .env.example .env
# .env を編集して DB 認証情報等を設定

# キャッシュクリア
php bin/console cache:clear --env=prod
```

## 動画フィード（/feed/）

EC-CUBE とは完全に独立した TikTok 風動画フィード。

- フロントエンド: https://tw.kyogokupro.com/feed/
- 管理画面: https://tw.kyogokupro.com/feed/admin/
- API: https://tw.kyogokupro.com/feed/api/
- Video Sitemap: https://tw.kyogokupro.com/feed/sitemap.xml
