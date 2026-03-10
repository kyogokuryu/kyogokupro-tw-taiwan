<?php
/**
 * AI Content Generator
 * Uses OpenAI API to generate:
 * - Title A/B variants (繁體中文)
 * - Description A/B variants (繁體中文)
 * - SEO article (繁體中文)
 */

class AiGenerator {
    private $apiKey;
    private $model;
    private $baseUrl;

    public function __construct() {
        $this->apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $this->model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4.1-mini';
        $this->baseUrl = defined('OPENAI_BASE_URL') ? OPENAI_BASE_URL : 'https://api.openai.com/v1';
    }

    /**
     * Generate all content: titles A/B, descriptions A/B, SEO article
     */
    public function generateAll($title, $description, $productNames = []) {
        $result = [
            'title_variant_a' => '',
            'title_variant_b' => '',
            'desc_variant_a' => '',
            'desc_variant_b' => '',
            'seo_article' => '',
        ];

        if (empty($this->apiKey) || $this->apiKey === 'YOUR_OPENAI_API_KEY') {
            error_log('AI Generator: No API key configured');
            return $result;
        }

        $productContext = '';
        if (!empty($productNames)) {
            $productContext = '關聯商品：' . implode('、', $productNames);
        }

        // Generate titles and descriptions
        $titlesAndDescs = $this->generateTitlesAndDescriptions($title, $description, $productContext);
        if ($titlesAndDescs) {
            $result = array_merge($result, $titlesAndDescs);
        }

        // Generate SEO article
        $seoArticle = $this->generateSeoArticle(
            $result['title_variant_a'] ?: $title,
            $result['desc_variant_a'] ?: $description,
            $productContext
        );
        if ($seoArticle) {
            $result['seo_article'] = $seoArticle;
        }

        return $result;
    }

    /**
     * Generate title A/B and description A/B
     */
    private function generateTitlesAndDescriptions($title, $description, $productContext) {
        $prompt = <<<EOT
你是KYOGOKU Professional（卡雅仕）台灣官方網站的內容行銷專家。
請根據以下影片資訊，生成兩組不同風格的標題和說明文（A版本和B版本）。

影片原始標題：{$title}
影片原始說明：{$description}
{$productContext}

要求：
1. 使用繁體中文
2. 標題要吸引人、包含關鍵字，適合台灣消費者
3. A版本：專業、信賴感風格（強調日本製造、專業技術、成分）
4. B版本：親切、生活化風格（強調使用體驗、效果、口碑）
5. 說明文要包含商品特色、使用情境、效果描述
6. 標題控制在30-50字以內
7. 說明文控制在80-150字以內
8. 包含適當的表情符號增加吸引力

請嚴格按照以下JSON格式回覆（不要加任何其他文字）：
{
  "title_variant_a": "A版本標題",
  "title_variant_b": "B版本標題",
  "desc_variant_a": "A版本說明文",
  "desc_variant_b": "B版本說明文"
}
EOT;

        $response = $this->callApi($prompt);
        if (!$response) return null;

        // Parse JSON from response
        $json = $this->extractJson($response);
        if ($json && isset($json['title_variant_a'])) {
            return [
                'title_variant_a' => $json['title_variant_a'] ?? '',
                'title_variant_b' => $json['title_variant_b'] ?? '',
                'desc_variant_a' => $json['desc_variant_a'] ?? '',
                'desc_variant_b' => $json['desc_variant_b'] ?? '',
            ];
        }

        return null;
    }

    /**
     * Generate SEO article
     */
    private function generateSeoArticle($title, $description, $productContext) {
        $prompt = <<<EOT
你是KYOGOKU Professional（卡雅仕）台灣官方網站的SEO內容撰寫專家。
請根據以下影片資訊，撰寫一篇SEO優化的繁體中文文章。

影片標題：{$title}
影片說明：{$description}
{$productContext}

文章要求：
1. 使用繁體中文，語氣專業但親切
2. 文章長度：800-1200字
3. 包含以下結構：
   - 引人入勝的開頭段落
   - 商品特色與技術說明（2-3段）
   - 使用方法或建議
   - 適合的使用族群
   - 總結與推薦
4. 自然融入SEO關鍵字（如：日本製造、角蛋白、護髮、美髮、KYOGOKU、卡雅仕等）
5. 使用HTML格式（h2, h3, p, ul/li, strong 標籤）
6. 不要使用h1標籤（頁面已有）
7. 適合台灣消費者閱讀習慣

請直接輸出HTML格式的文章內容，不要加任何其他說明文字。
EOT;

        $response = $this->callApi($prompt, 2000);
        if (!$response) return null;

        // Clean up response - remove markdown code blocks if present
        $response = trim($response);
        $response = preg_replace('/^```html?\s*/i', '', $response);
        $response = preg_replace('/\s*```\s*$/', '', $response);

        return $response;
    }

    /**
     * Call OpenAI API
     */
    private function callApi($prompt, $maxTokens = 1000) {
        $url = rtrim($this->baseUrl, '/') . '/chat/completions';

        $payload = json_encode([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => '你是KYOGOKU Professional台灣官方網站的AI內容助手，專門為美髮產品撰寫繁體中文行銷內容。'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.8,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('AI Generator cURL error: ' . $error);
            return null;
        }

        if ($httpCode !== 200) {
            error_log('AI Generator API error (HTTP ' . $httpCode . '): ' . $response);
            return null;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            error_log('AI Generator: Invalid API response: ' . substr($response, 0, 500));
            return null;
        }

        return trim($data['choices'][0]['message']['content']);
    }

    /**
     * Extract JSON from a response that might contain extra text
     */
    private function extractJson($text) {
        // Try direct parse first
        $json = json_decode($text, true);
        if ($json) return $json;

        // Try to find JSON in the text
        if (preg_match('/\{[^{}]*"title_variant_a"[^{}]*\}/s', $text, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) return $json;
        }

        // Try to find JSON block between ```
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $json = json_decode($matches[1], true);
            if ($json) return $json;
        }

        // Last resort: find first { to last }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $jsonStr = substr($text, $start, $end - $start + 1);
            $json = json_decode($jsonStr, true);
            if ($json) return $json;
        }

        error_log('AI Generator: Failed to parse JSON from response: ' . substr($text, 0, 500));
        return null;
    }
}
