<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesImagesDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticlesImagesDbAdapterTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @return ArticlesImagesDbAdapter
     */
    private function createArticleImagesDbAdapter()
    {
        return new ArticlesImagesDbAdapter();
    }

    private function getImportImagePath()
    {
        return 'file://' . realpath(dirname(__FILE__)) . '/../../../../Helper/ImportFiles/sw-icon_blue128.png';
    }

    private function getInvalidImportImagePath()
    {
        return 'file://' . realpath(dirname(__FILE__)) . '/../../../../Helper/ImportFiles/invalid_image_name.png';
    }

    public function test_write_should_throw_exception_if_records_are_empty()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine neuen Artikelbilder gefunden.');
        $articlesImagesDbAdapter->write([]);
    }

    public function test_write_should_throw_exception_having_wrong_path()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => '/../../../image.png',
                    'description' => 'testimport1',
                    'thumbnail' => 1
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nicht-unterstütztes Schema .');
        $articlesImagesDbAdapter->write($records);
    }

    public function test_new_article_image_should_be_written_to_database()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => $this->getImportImagePath(),
                    'description' => 'testimport1',
                    'thumbnail' => 1
                ]
            ]
        ];
        $articlesImagesDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $articleId = $dbalConnection->executeQuery('SELECT articleID FROM s_articles_details WHERE orderNumber="SW10001"')->fetch(\PDO::FETCH_COLUMN);
        $image = $dbalConnection->executeQuery('SELECT * FROM s_articles_img WHERE description = "testimport1"')->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals($records['default'][0]['description'], $image['description']);
        $this->assertEquals($articleId, $image['articleID']);
        $this->assertEquals('png', $image['extension']);
    }

    public function test_write_with_invalid_order_number_throws_exception()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'invalid-order-number',
                    'image' => $this->getImportImagePath(),
                    'description' => 'testimport1'
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Artikel mit Nummer invalid-order-number existiert nicht.');
        $articlesImagesDbAdapter->write($records);
    }

    public function test_write_with_not_existing_image_throws_exception()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => $this->getInvalidImportImagePath(),
                    'description' => 'testimport1'
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann file://' . realpath(dirname(__FILE__)) . '/../../../../Helper/ImportFiles/invalid_image_name.png nicht zum Lesen öffnen');
        $articlesImagesDbAdapter->write($records);
    }
}
