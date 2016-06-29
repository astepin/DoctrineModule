<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace DoctrineModule\Service;

use Doctrine\Common\Cache\CacheProvider;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use RuntimeException;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

/**
 * Cache ServiceManager factory
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Kyle Spraggs <theman@spiffyjr.me>
 */
class CacheFactory extends AbstractFactory
{
    /**
     * {@inheritDoc}
     */
    public function getOptionsClass()
    {
        return 'DoctrineModule\Options\Cache';
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var $options \DoctrineModule\Options\Cache */
        $options = $this->getOptions($container, 'cache');
        $class   = $options->getClass();

        if (!$class) {
            throw new RuntimeException('Cache must have a class name to instantiate');
        }

        $instance = $options->getInstance();

        if (is_string($instance) && $container->has($instance)) {
            $instance = $container->get($instance);
        }

        switch ($class) {
            case 'Doctrine\Common\Cache\FilesystemCache':
                $cache = new $class($options->getDirectory());
                break;

            case 'DoctrineModule\Cache\ZendStorageCache':
            case 'Doctrine\Common\Cache\PredisCache':
                $cache = new $class($instance);
                break;

            default:
                $cache = new $class;
        }

        if ($cache instanceof MemcacheCache) {
            /* @var $cache MemcacheCache */
            $cache->setMemcache($instance);
        } elseif ($cache instanceof MemcachedCache) {
            /* @var $cache MemcachedCache */
            $cache->setMemcached($instance);
        } elseif ($cache instanceof RedisCache) {
            /* @var $cache RedisCache */
            $cache->setRedis($instance);
        }

        if ($cache instanceof CacheProvider && ($namespace = $options->getNamespace())) {
            $cache->setNamespace($namespace);
        }

        return $cache;
    }
}
