<?php

namespace Bloatless\MuninNode\Plugins;

class Memory implements PluginInterface
{
    private const IDENTIFIER = 'memory';

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * Maps system memory-field-names to keys used within munin.
     *
     * @var array $fieldMap
     */
    protected $fieldMap = [
        'Slab' => 'slab',
        'SwapCached' => 'swap_cache',
        'PageTables' => 'page_tables',
        'VmallocUsed' => 'vmalloc_used',
        'MemFree' => 'free',
        'Buffers' => 'buffers',
        'Cached' => 'cached',
        'Committed_AS' => 'committed',
        'Mapped' => 'mapped',
        'Active' => 'active',
        'ActiveAnon' => 'active_anon',
        'ActiveCache' => 'active_cache',
        'Inactive' => 'inactive',
        'Inact_dirty' => 'inact_dirty',
        'Inact_laundry' => 'inact_laundry',
        'Inact_clean' => 'inact_clean',
    ];

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    /**
     * @inheritdoc
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        $meminfo = $this->fetchMeminfo();

        // determine graph order depending on fields provided by system
        $graphOrder =  ['apps'];
        if (isset($meminfo['PageTables'])) {
            $graphOrder[] = 'page_tables';
        }
        if (isset($meminfo['SwapCached'])) {
            $graphOrder[] = 'swap_cache';
        }
        if (isset($meminfo['Slab'])) {
            $graphOrder[] = 'slab';
        }
        $graphOrder = array_merge($graphOrder, ['cached', 'buffers', 'free', 'swap']);

        // graph config
        $configuration = [
            'graph_args' => '--base 1024 -l 0 --upper-limit '. $meminfo['MemTotal'],
            'graph_vlabel' => 'Bytes',
            'graph_title' => 'Memory usage',
            'graph_category' => 'system',
            'graph_info' => 'This graph shows what the machine uses memory for.',
            'graph_order' => implode(' ', $graphOrder),
        ];

        // value config:
        $configuration += [
            'apps.label' => 'apps',
            'apps.draw' => 'AREA',
            'apps.info' => 'Memory used by user-space applications.',
            'buffers.label' => 'buffers',
            'buffers.draw' => 'STACK',
            'buffers.info' => 'Block device (e.g. harddisk) cache. Also where "dirty" blocks are stored until written.',
            'swap.label' => 'swap',
            'swap.draw' => 'STACK',
            'swap.info' => 'Swap space used.',
            'cached.label' => 'cache',
            'cached.draw' => 'STACK',
            'cached.info' => 'Parked file data (file content) cache.',
            'free.label' => 'unused',
            'free.draw' => 'STACK',
            'free.info' => 'Wasted memory. Memory that is not used for anything at all.',
        ];

        if (isset($meminfo['Slab'])) {
            $configuration += [
                'slab.label' => 'slab_cache',
                'slab.draw' => 'STACK',
                'slab.info' => 'Memory used by the kernel (major users are caches like inode, dentry, etc).',
            ];
        }

        if (isset($meminfo['SwapCached'])) {
            $configuration += [
                'swap_cache.label' => 'swap_cache',
                'swap_cache.draw' => 'STACK',
                'swap_cache.info' => 'A piece of memory that keeps track of pages that have been fetched from swap but not yet been modified.',
            ];
        }

        if (isset($meminfo['PageTables'])) {
            $configuration += [
                'page_tables.label' => 'page_tables',
                'page_tables.draw' => 'STACK',
                'page_tables.info' => 'Memory used to map between virtual and physical memory addresses.',
              ];
        }

        if (isset($meminfo['VmallocUsed'])) {
            $configuration += [
                'vmalloc_used.label' => 'vmalloc_used',
                'vmalloc_used.draw' => 'LINE2',
                'vmalloc_used.info' => 'VMalloc (kernel) memory used',
            ];
        }

        if (isset($meminfo['Committed_AS'])) {
            $configuration += [
                'committed.label' => 'committed',
                'committed.draw' => 'LINE2',
                'committed.info' => 'The amount of memory allocated to programs. "Overcommitting is normal, but may indicate memory leaks.',
            ];
        }

        if (isset($meminfo['Mapped'])) {
            $configuration += [
                'mapped.label' => 'mapped',
                'mapped.draw' => 'LINE2',
                'mapped.info' => 'All mmap()ed pages.',
            ];
        }

        if (isset($meminfo['Active'])) {
            $configuration += [
                'active.label' => 'active',
                'active.draw' => 'LINE2',
                'active.info' => 'Memory recently used. Not reclaimed unless absolutely necessary.',
            ];
        }

        if (isset($meminfo['ActiveAnon'])) {
            $configuration += [
                'active_anon.label' => 'active_anon',
                'active_anon.draw' => 'LINE1',
            ];
        }

        if (isset($meminfo['ActiveCache'])) {
            $configuration += [
                'active_cache.label' => 'active_cache',
                'active_cache.draw' => 'LINE1',
            ];
        }

        if (isset($meminfo['Inactive'])) {
            $configuration += [
                'inactive.label' => 'inactive',
                'inactive.draw' => 'LINE2',
                'inactive.info' => 'Memory not currently used.',
            ];
        }

        if (isset($meminfo['Inact_dirty'])) {
            $configuration += [
                'inact_dirty.label' => 'inactive_dirty',
                'inact_dirty.draw' => 'LINE1',
                'inact_dirty.info' => 'Memory not currently used, but in need of being written to disk.',
            ];
        }

        if (isset($meminfo['Inact_laundry'])) {
            $configuration += [
                'inact_laundry.label' => 'inactive_laundry',
                'inact_laundry.draw' => 'LINE1',
            ];
        }

        if (isset($meminfo['Inact_clean'])) {
            $configuration += [
                'inact_clean.label' => 'inactive_clean',
                'inact_clean.draw' => 'LINE1',
                'inact_clean.info' => 'Memory not currently used.',
            ];
        }

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getValues(): array
    {
        $meminfo = $this->fetchMeminfo();

        // map system keys to keys used by munin
        $values = [];
        foreach ($meminfo as $fieldName => $fieldValue) {
            $key = $this->fieldMap[$fieldName] ?? '';
            if (!empty($key)) {
                $values[$key] = $fieldValue;
            }
        }

        // Set fields required for calculations to zero (if not provided by system)
        $requiredFields = [
            'MemFree',
            'Buffers',
            'Cached',
            'Slab',
            'PageTables',
            'SwapCached',
            'SwapFree',
            'SwapTotal'
        ];
        foreach ($requiredFields as $fieldName) {
            if (!isset($meminfo[$fieldName])) {
                $meminfo[$fieldName] = 0;
            }
        }

        // Add calculated values
        $values['apps'] = $meminfo['MemTotal']
            - $meminfo['MemFree']
            - $meminfo['Buffers']
            - $meminfo['Cached']
            - $meminfo['Slab']
            - $meminfo['PageTables']
            - $meminfo['SwapCached'];

        $values['swap'] = $meminfo['SwapTotal'] - $meminfo['SwapFree'];

        return $values;
    }

    /**
     * Fetches memory information from system.
     *
     * @return array
     */
    protected function fetchMeminfo(): array
    {
        $meminfoContent = file_get_contents('/proc/meminfo');
        preg_match_all('/^(\w+):\s*(\d+)\s+kb/im', $meminfoContent, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return [];
        }

        $meminfo = [];
        foreach ($matches as $match) {
            $meminfo[$match[1]] = $match[2] * 1024;
        }

        return $meminfo;
    }
}
