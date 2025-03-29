<?php

namespace app\services;

use app\components\responseFunction\Result;
use GuzzleHttp\Client;

class TranslationService
{

    private static $api_key = '0c66676b39cc4cf896349a113eb05ff0';
    private static $endpoint = "https://joyka.openai.azure.com/openai/deployments/";
    private static $deployment_id = 'chat_translate_GPT4';
    private static $api_version = '2024-08-01-preview';


    public static function translateMessage(
        $message,
        $mesageId
    ) {
        $instruction = "Imagine that you are a professional linguist and translator.";
        $prompt = "
        Translate the following text into 3 languages: English, Russian, Chinese.
            - Do NOT swap languages: 
            - 'ru' must contain only Russian translations.
            - 'en' must contain only English translations.
            - 'zh' must contain only Chinese translations.  
            - Maintain punctuation, spacing, and capitalization as in the original text. Do not add or remove any text.
            - Provide only literal translations, avoiding interpretations or additional commentary.  
            - Do not perform any mathematical operations.  
            - Use transliteration for slang terms or abbreviations.  
            - If a word contains an error, suggest a similar word in meaning or transliterate it.  
            - If unsure of a translation, default to transliteration.  
            - Do not translate Russian words into English or English words into Russian unless specified.  
            - Return only the JSON object with no surrounding text or formatting.  
            - Clear all previous conversation context after completing the translation. 
            - For example, if the word \"товор\" contains an error, replace it with a similar word and transliterate it for English and Chinese.
            - Only the json object, without phrases and notes.
            - A word in the middle of a text with a capital letter is not always a name of something. Understand from the context whether it is a name or not. If not, translate it as a regular word. If it is a name, leave it unchanged.
            - Translate the entire text from beginning to end. Do not shorten it, even if repetitions are used. Your task is simply to translate from one language to another.
            - But do not translate brand names (e.g., Apple, Sony, Samsung, etc).
            - Also, adapt the translation to natural language structures while preserving the overall meaning of the phrase.
            - Structure the response as a JSON object:
            {{ \"ru\": \"translation in Russian\", \"en\": \"translation in English\", \"zh\": \"translation in Chinese\" }}, and nothing else.
            Original text is: " . $message;
        $translation = self::translate($instruction, $prompt);
        return $translation;
    }

    public static function translateAttributes(
        $name,
        $description,
        $type,
        $id
    ) {

        $instruction = "Imagine that you are a professional linguist and translator.";
        $prompt = "
            Please translate the following product name and description into three languages: English, Russian, and Chinese.  
            - Do NOT swap languages: 
            - 'ru' must contain only Russian translations.
            - 'en' must contain only English translations.
            - 'zh' must contain only Chinese translations.  
            - Maintain punctuation, spacing, and capitalization as in the original text.  
            - Provide only literal translations, avoiding interpretations or additional commentary.  
            - Do not perform any mathematical operations.  
            - Use transliteration for slang terms or abbreviations.  
            - If a word contains an error, suggest a similar word in meaning or transliterate it.  
            - If unsure of a translation, default to transliteration.  
            - Do not translate Russian words into English or English words into Russian unless specified.  
            - Return only the JSON object with no surrounding text or formatting.  
            - Clear all previous conversation context after completing the translation. 
            - Structure the response as a JSON object: {{
                \"ru\": {{
                    \"name\": \"translated product name in Russian\",
                    \"description\": \"translated product description in Russian\"
                }},
                \"en\": {{
                    \"name\": \"translated product name in English\",
                    \"description\": \"translated product description in English\"
                }},
                \"zh\": {{
                    \"name\": \"translated product name in Chinese\",
                    \"description\": \"translated product description in Chinese\"
                }}
            }}, and nothing else. 
            Product name: {$name} 
            Product description: {$description} 
            For instance, if the word \"товор\" contains an error, replace it with a similar word.
        ";

        $data = [
            "messages" => [
                ["role" => "system", "content" => $instruction],
                ["role" => "user", "content" => $prompt]
            ]
        ];

        \Yii::$app->queue->push(new \app\jobs\Translate\AttributeTranslateJob([
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'id' => $id,
            'data' => $data
        ]));

        return;
    }

    public static function translate(
        $data
    ) {

        $url = self::$endpoint . self::$deployment_id . "/chat/completions?api-version=" . self::$api_version;
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . self::$api_key,
            "api-key: " . self::$api_key
        ];
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $data,
            ]);
            $result = json_decode($response->getBody(), true);
            return $result["choices"][0]["message"]["content"];
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}
