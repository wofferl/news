<?php

/**
 * @author Sean Molenaar <sean@seanmolenaar.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\News\Tests\Config;

use OCA\News\Config\FetcherConfig;
use OCA\News\Fetcher\Client\FeedIoClient;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class FetcherConfigTest
 *
 * TODO: Improve this
 *
 * @package OCA\News\Tests\Config
 */
class FetcherConfigTest extends TestCase
{
    /** @var MockObject|IAppConfig */
    protected $config;

    /** @var MockObject|IConfig */
    protected $sysconfig;

    /** @var MockObject|IAppManager */
    protected $appmanager;

    /** @var FetcherConfig */
    protected $class;

    /** @var MockObject|LoggerInterface */
    protected $logger;

    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(IAppConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sysconfig = $this->getMockBuilder(IConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->appmanager = $this->getMockBuilder(IAppManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test a valid call will work
     */
    public function testGetClient()
    {
        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $this->assertInstanceOf(FeedIoClient::class, $this->class->getClient());
    }

    public function testGetUserAgent()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10],
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('123.45');

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'NextCloud-News/123.45';
        $response = $this->class->getUserAgent();
        $this->assertEquals($expected, $response);
    }

    public function testGetUserAgentUnknownVersion()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10]
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('1.0');

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'NextCloud-News/1.0';
        $response = $this->class->getUserAgent();
        $this->assertEquals($expected, $response);
    }

    public function testStandardPortPreserved()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10]
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('1.0');

        $this->sysconfig->expects($this->exactly(2))
            ->method('getSystemValue')
            ->willReturnMap([
                ['proxy', null, 'http://192.168.178.1:80'],
                ['proxyuserpwd', null, null]
            ]);

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'http://192.168.178.1:80';
        $response = $this->class->getProxy();
        $this->assertEquals($expected, $response);
    }

    public function testProxyPortPreserved()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10]
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('1.0');

        $this->sysconfig->expects($this->exactly(2))
            ->method('getSystemValue')
            ->willReturnMap([
                ['proxy', null, 'http://192.168.178.1:8080'],
                ['proxyuserpwd', null, null]
            ]);

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'http://192.168.178.1:8080';
        $response = $this->class->getProxy();
        $this->assertEquals($expected, $response);
    }

    public function testProxyIPNoSchema()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10]
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('1.0');

        $this->sysconfig->expects($this->exactly(2))
            ->method('getSystemValue')
            ->willReturnMap([
                ['proxy', null, '192.168.178.1:8080'],
                ['proxyuserpwd', null, null]
            ]);

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'http://192.168.178.1:8080';
        $response = $this->class->getProxy();
        $this->assertEquals($expected, $response);
    }

    public function testProxyWithAuth()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10]
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('1.0');

        $this->sysconfig->expects($this->exactly(2))
            ->method('getSystemValue')
            ->willReturnMap([
                ['proxy', null, 'https://192.168.178.1:443'],
                ['proxyuserpwd', null, 'admin:password']
            ]);

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'https://admin:password@192.168.178.1:443';
        $response = $this->class->getProxy();
        $this->assertEquals($expected, $response);
    }

    public function testDomainProxy()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10]
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('1.0');

        $this->sysconfig->expects($this->exactly(2))
            ->method('getSystemValue')
            ->willReturnMap([
                ['proxy', null, 'apt.domain.net:3142'],
                ['proxyuserpwd', null, null]
            ]);

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'http://apt.domain.net:3142';
        $response = $this->class->getProxy();
        $this->assertEquals($expected, $response);
    }

    public function testDomainProxyWithAuth()
    {
        $this->config->expects($this->exactly(2))
            ->method('getValueInt')
            ->willReturnMap([
                ['news', 'feedFetcherTimeout', 60, false, 60],
                ['news', 'maxRedirects', 10, false, 10]
            ]);

        $this->appmanager->expects($this->exactly(1))
            ->method('getAppVersion')
            ->willReturn('1.0');

        $this->sysconfig->expects($this->exactly(2))
            ->method('getSystemValue')
            ->willReturnMap([
                ['proxy', null, 'apt.domain.net:3142'],
                ['proxyuserpwd', null, 'admin:password']
            ]);

        $this->class = new FetcherConfig($this->config, $this->sysconfig, $this->appmanager, $this->logger);

        $expected = 'http://admin:password@apt.domain.net:3142';
        $response = $this->class->getProxy();
        $this->assertEquals($expected, $response);
    }
}
