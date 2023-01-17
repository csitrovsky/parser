<?php


namespace app\console;


use app\src\Database;
use app\src\spout\Excel;
use app\src\spout\ExcelColumn;
use app\src\telegram\Bot;
use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use GuzzleHttp\Client;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


use function array_shift;
use function ceil;
use function count;
use function date;
use function dirname;
use function end;
use function explode;
use function file_put_contents;
use function html_entity_decode;
use function htmlentities;
use function implode;
use function is_array;
use function is_dir;
use function json_decode;
use function parse_url;
use function preg_match;
use function print_r;
use function rtrim;
use function sleep;
use function str_replace;
use function trim;


/**
 * @property \app\src\Database $_database
 * @property false|string      $_from
 * @property array             $_response
 * @property array             $_data
 */
class Products extends Command
{
    
    /**
     * @var float
     */
    private $ratio = 1.00;
    
    /**
     * @var int
     */
    private $size = 100;
    
    /**
     * @param        $keys
     * @param        $create_data
     * @param        $file
     * @param string $col_delimiter
     * @param string $row_delimiter
     *
     * @return false|string
     */
    public function kama_create_csv_file(
        $keys,
        $create_data,
        $file = null,
        string $col_delimiter = ';',
        string $row_delimiter = "\r\n"
    ) {
        
        if (!is_array($create_data)) {
            return false;
        }
        if ($file && !is_dir(dirname($file))) {
            return false;
        }
        $CSV_str = implode($col_delimiter, $keys) . $row_delimiter;
        foreach ($create_data as $row) {
            $cols = [];
            foreach ($row as $col_val) {
                if ($col_val && preg_match('/[",;\r\n]/', $col_val)) {
                    if ($row_delimiter === "\r\n") {
                        $col_val = str_replace(["\r\n", "\r"], ['\n', ''], $col_val);
                    } elseif ($row_delimiter === "\n") {
                        $col_val = str_replace(["\n", "\r\r"], '\r', $col_val);
                    }
                    $col_val = str_replace('"', '""', $col_val);
                    $col_val = '"' . $col_val . '"';
                }
                $cols[] = $col_val;
            }
            $CSV_str .= implode($col_delimiter, $cols) . $row_delimiter;
        }
        $CSV_str = rtrim($CSV_str, $row_delimiter);
        if ($file) {
            $CSV_str = html_entity_decode(htmlentities($CSV_str, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'ISO-8859-15');
            $done = file_put_contents($file, $CSV_str);
            
            return $done ? $CSV_str : false;
        }
        
        return $CSV_str;
    }
    
    /**
     * @return void
     */
    protected function configure(): void
    {
        
        $this->setName('products:update');
        $this->setDescription('Обновление цем');
    }
    
    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        # Начала скрипта
        $output->writeln('Начала обновления цен!');
        
        # Используем библиотеку запросов к базе данных
        $this->_database = (new Database());
        
        # Передаем первичные входные параметры
        $this->_from = date('Y-m-d');
        $this->_response = ['products' => []];
        $this->_data = [];
        
        #
        $output->writeln("[$this->_from]: Начинается загрузка данных!");
        
        #
        $count = $this->getCountProducts();
        $page = ($count / $this->size);
        $output->writeln("Количество товаров: $count; Количество страниц: $page.");
        
        for ($i = 0; $i <= ($page + 1); $i++) {
            $output->writeln("Страница: (" . ($i + 1) . ") из " . $this->round($page));
            $products = $this->getProducts($i);
            
            if (!count($products)) {
                $output->writeln('[^.^] Empty...');
                break;
            }
            
            foreach ($products as $product) {
                $parse = parse_url($product['url'], PHP_URL_HOST);
                if ($parse !== '...') {
                    continue;
                }
                $url = explode('/', trim(str_replace('\\', '/', $product['url']), '/'));
                
                $pet = $this->getPrice(end($url));
                $allowed = $product['allowed'];
                
                if ($pet->id !== end($url)) {
                    $price = $pet_price = 0.00;
                } else {
                    $price = $pet_price = ($pet->price ?? 0.00);
                }
                
                if ((int)$product['pet'] === (int)$price) {
                    continue;
                }
                
                if (($ratio = $product['ratio']) > 0) {
                    $price = @$ratio * $price;
                }
                
                if ((int)$price > 0) {
                    if ($this->ratio) {
                        $price = $this->ratio * $price;
                    }
                    if ($allowed) {
                        $price = $allowed * $price;
                    }
                }
                
                $set = [
                    '`pet` = :pet',
                    '`newprice` = :newprice',
                    '`model` = :model',
                ];
                
                $sql = 'UPDATE `oc_product` SET ' . implode(',', $set);
                $sql .= ' WHERE `product_id` = :product_id;';
                
                $this->_database->query($sql, [
                    ':pet' => $pet_price,
                    ':newprice' => $this->round($price),
                    ':model' => $pet->id,
                    
                    ':product_id' => $product['id'],
                ]);
                
                if ((int)$price > 0) {
                    $this->_response['products'][$pet->id] = [
                        'id' => $product['id'],
                        'name' => $pet->name,
                        'old_price' => $product['price'],
                        'new_price' => $this->round($price),
                        'pet_price' => $pet_price,
                        'link_url' => '...' . $pet->link_url,
                    ];
                }
            }
            sleep(1);
        }
        
        $date = date(DATE_RFC822);
        if (count($this->_response['products']) > 15) {
            // $file = INC_ROOT . '/products.csv';
            // $keys = array_keys(array_values($this->_response['products'])[0]);
            // $this->kama_create_csv_file($keys, $this->_response['products'], $file);
            
            $file = INC_ROOT . '/products.xlsx';
            $columns = [
                new ExcelColumn('ID', 'id', Cell::TYPE_NUMERIC),
                new ExcelColumn('Название', 'name', Cell::TYPE_STRING),
                new ExcelColumn('Старая цена', 'old_price', Cell::TYPE_NUMERIC),
                new ExcelColumn('Новая цена', 'new_price', Cell::TYPE_NUMERIC),
                new ExcelColumn('Цена с Пет', 'pet_price', Cell::TYPE_NUMERIC),
                new ExcelColumn('Ссылка на товар', 'link_url', Cell::TYPE_STRING),
            ];
            try {
                Excel::export('xlsx', $file, $columns, $this->_response['products']);
                Bot::message("[$date] *Обновлены цены:* " . count($this->_response['products']));
                Bot::document($file);
            } catch (IOException|UnsupportedTypeException|WriterNotOpenedException $e) {
                Bot::message("*Ошибка*: " . print_r($e->getMessage(), 1));
            }
        } else {
            foreach ($this->_response as $key => $item) {
                if (empty($item)) {
                    continue;
                }
                sleep(3);
                
                $urls = [];
                foreach ($item as $datum) {
                    $urls[] = "[{$datum['name']}](" . $datum['link_url'] . ")";
                }
                
                $message = '';
                switch ($key) {
                    case 'products':
                        $message = 'Обновлены цены:';
                        break;
                    default:
                        break;
                }
                
                Bot::message("[$date] *$message:* \n" . PHP_EOL . implode(PHP_EOL, $urls), [
                    'parse_mode' => '',
                ]);
            }
        }
        
        if ($this->_response['products'] <= 0) {
            Bot::message("*Ошибка*: " . ' Нет данных для обновления.');
        }
        
        return 0;
    }
    
    /**
     * @return mixed
     */
    private function getCountProducts()
    {
        
        $this->_database->select('oc_product', ['COUNT(*)']);
        $this->_database->where([
            '`status` != :status',
            "`mpn` != ''",
            "`mpn` != '#N/A'",
        ]);
        
        return $this->_database->query($this->_database->_query, [
            ':status' => 0,
        ])->fetch(PDO::FETCH_COLUMN);
    }
    
    /**
     * @param $num
     *
     * @return float|int
     */
    private function round($num)
    {
        
        $num /= 10 ** 0;
        $num = ceil($num);
        
        return $num * (10 ** 0);
    }
    
    /**
     * @param int $i
     *
     * @return array|false
     */
    private function getProducts(int $i)
    {
        
        $this->_database->select('oc_product', [
            '`product_id` AS `id`',
            '`name`',
            '`price`',
            '`isbn` AS `allowed`',
            '`ean` AS `ratio`',
            '`mpn` AS `url`',
            '`pet`',
        ]);
        $this->_database->where([
            '`status` != :status',
            "`mpn` != ''",
            "`mpn` != '#N/A'",
        ]);
        $this->_database->order(['`id` ASC']);
        $this->_database->limit($this->size);
        $this->_database->offset($this->size * $i);
        
        return $this->_database->query($this->_database->_query, [
            ':status' => 0,
        ])->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * @param $id
     *
     * @return false|object
     */
    private function getPrice($id)
    {
        
        $url = '...';
        $params = [
            'st' => $id,
            'apiKey' => '...',
            'strategy' => 'vectors_strict,zero_queries',
            'size' => 1,
            'offset' => 0,
            'fullData' => true,
            'regionId' => 'msk',
        ];
        
        $response = (new Client())->get($url, [
            'query' => $params,
        ]);
        $contents = $response->getBody()->getContents();
        $result = json_decode($contents, true, 512);
        
        if (empty($result)) {
            return false;
        }
        
        return (object)array_shift($result['products']);
    }
}