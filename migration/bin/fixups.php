<?php
/**
 * Corrections for malformed annotations in the legacy tree that Rector cannot
 * resolve on its own. Each one records what Doctrine 2 *actually* did, so the
 * ported mapping keeps pointing at the same live database columns.
 *
 * usage: php migration/bin/fixups.php
 */
$root = dirname(__DIR__, 2);
$applied = 0; $skipped = 0;

$fixes = [
    // User::$globalOption - legacy docblock had a stray "* " before @ORM\JoinColumns,
    // so Doctrine 2's driver took the standalone @ORM\JoinColumn(onDelete="CASCADE")
    // and defaulted the column to globalOption_id. Rector emitted BOTH JoinColumns,
    // which ORM 3 would read as a *composite* FK against a table that has one column.
    [
        'file' => 'next/src/User/Entity/User.php',
        'from' => "    #[ORM\\JoinColumn(name: 'globalOption_id', referencedColumnName: 'id')]\n"
                . "    #[ORM\\JoinColumn(onDelete: 'CASCADE')]\n",
        'to'   => "    #[ORM\\JoinColumn(name: 'globalOption_id', referencedColumnName: 'id', onDelete: 'CASCADE')]\n",
    ],
    // User::$globalOptionAgents is a OneToMany; join columns live on the owning side,
    // so these were inert under Doctrine 2 and must not become a composite FK now.
    [
        'file' => 'next/src/User/Entity/User.php',
        'from' => "    #[ORM\\JoinColumn(name: 'globalOptionAgent_id', referencedColumnName: 'id')]\n"
                . "    #[ORM\\JoinColumn(onDelete: 'CASCADE')]\n",
        'to'   => '',
    ],
    // Truncated "@ORM\\JoinColumns({" fragments left in docblocks. ORM 3 has no
    // annotation driver so they are inert, but they are broken text - drop them.
    [
        'file' => 'next/src/User/Entity/User.php',
        'from' => "     *  * @ORM\\JoinColumns({\n",
        'to'   => '',
    ],
    [
        'file' => 'next/src/User/Entity/User.php',
        'from' => "\t/** * @ORM\\JoinColumns({\n     **/\n",
        'to'   => '',
    ],
    // BusinessConfig: the legacy file had two docblocks in a row, so only the
    // second attached to $businessVendorStocks and Doctrine 2 never saw the
    // BusinessBatch mapping. Rector kept the live one; drop the dead text.
    [
        'file' => 'next/src/Business/Entity/BusinessConfig.php',
        'from' => "\t/**\n     * @ORM\\OneToMany(targetEntity=\"App\\Business\\Entity\\BusinessBatch\" , mappedBy=\"businessConfig\")\n     **/\n",
        'to'   => '',
    ],
    // FOSUserBundle has no Symfony 5+ release. App\Security\Model\BaseUser
    // reproduces its mapped superclass column-for-column (see that file).
    [
        'file' => 'next/src/User/Entity/User.php',
        'from' => "use FOS\\UserBundle\\Model\\User as BaseUser;",
        'to'   => "use App\\Security\\Model\\BaseUser;",
    ],
    [
        'file' => 'next/src/User/Entity/Group.php',
        'from' => "use FOS\\UserBundle\\Model\\Group as BaseGroup;",
        'to'   => "use App\\Security\\Model\\BaseGroup;",
    ],
    // FOSCommentBundle is abandoned; App\Comment\Model reproduces its superclasses.
    [
        'file' => 'next/src/Content/Entity/Thread.php',
        'from' => "use FOS\\CommentBundle\\Entity\\Thread as BaseThread;",
        'to'   => "use App\\Comment\\Model\\BaseThread;",
    ],
    [
        'file' => 'next/src/Content/Entity/Comment.php',
        'from' => "use FOS\\CommentBundle\\Entity\\Comment as BaseComment;",
        'to'   => "use App\\Comment\\Model\\BaseComment;",
    ],
    [
        'file' => 'next/src/Content/Entity/Comment.php',
        'from' => "use FOS\\CommentBundle\\Model\\SignedCommentInterface;",
        'to'   => "use App\\Comment\\Model\\SignedCommentInterface;",
    ],
    // Notepad only referenced FOSUser's model in @return/@param docblocks; the
    // actual association already targets the app's own User entity.
    [
        'file' => 'next/src/DomainUser/Entity/Notepad.php',
        'from' => "use FOS\\UserBundle\\Model\\User;",
        'to'   => "use App\\User\\Entity\\User;",
    ],
    // ORM 3 removed the UUID generator (it ran MySQL's UUID(), i.e. a v1 UUID).
    // Generate the same shape in PHP instead and let Doctrine store it as-is;
    // existing `pos` rows keep their ids. Doctrine hydrates without calling the
    // constructor, so this only fires for genuinely new entities.
    [
        'file' => 'next/src/Pos/Entity/Pos.php',
        'from' => "    #[ORM\\Id]\n    #[ORM\\Column(name: 'id', type: 'guid')]\n    #[ORM\\GeneratedValue(strategy: 'UUID')]\n    protected \$id;\n",
        'to'   => "    #[ORM\\Id]\n    #[ORM\\Column(name: 'id', type: 'guid')]\n    #[ORM\\GeneratedValue(strategy: 'NONE')]\n    protected \$id;\n\n"
                . "    public function __construct()\n    {\n        \$this->id = (string) \\Symfony\\Component\\Uid\\Uuid::v1();\n    }\n",
    ],
];

foreach ($fixes as $fix) {
    $p = "$root/" . $fix['file'];
    if (!is_file($p)) { fwrite(STDERR, "missing {$fix['file']}\n"); continue; }
    $s = (string) file_get_contents($p);
    if (!str_contains($s, $fix['from'])) { $skipped++; continue; }
    file_put_contents($p, str_replace($fix['from'], $fix['to'], $s));
    $applied++;
}

// Duplicated #[ORM\Entity]: four legacy classes carry a bare @ORM\Entity *and* one
// with repositoryClass. Doctrine 2's AnnotationDriver re-keyed class annotations by
// class name, so the last (repositoryClass) one was live. PHP 8 rejects the repeat
// outright, so drop the bare duplicate and keep the mapping that was actually used.
$dupes = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/next/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $s = (string) file_get_contents($f->getPathname());
    if (substr_count($s, '#[ORM\\Entity') < 2) continue;
    if (!str_contains($s, '#[ORM\\Entity(repositoryClass')) continue;
    $out = preg_replace('/^[ \t]*#\[ORM\\\\Entity\]\n/m', '', $s, 1, $n);
    if ($n) { file_put_contents($f->getPathname(), $out); $dupes++; }
}
printf("removed bare duplicate #[ORM\\Entity] from %d classes\n", $dupes);

// ORM 3 removed EntityManager::merge() and with it the "merge" cascade, which is
// now a hard mapping error. The legacy app calls merge() exactly once (in a dev
// command), so the cascade was already inert - drop it and dedupe the list.
$cascades = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/next/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $s = (string) file_get_contents($f->getPathname());
    if (!str_contains($s, 'cascade: [')) continue;

    $out = preg_replace_callback('/cascade: \[([^\]]*)\]/', static function (array $m): string {
        $opts = array_map(static fn (string $o): string => trim($o), explode(',', $m[1]));
        $opts = array_values(array_unique(array_filter(
            $opts,
            static fn (string $o): bool => $o !== '' && $o !== "'merge'"
        )));

        return $opts === [] ? 'cascade: []' : 'cascade: [' . implode(', ', $opts) . ']';
    }, $s, -1, $n);

    if ($n && $out !== $s) { file_put_contents($f->getPathname(), $out); $cascades++; }
}
printf("normalised cascade options in %d files\n", $cascades);

// Orphaned docblocks: a docblock separated from its property by a blank line and a
// second docblock never attached to anything, so Doctrine 2 ignored it. Rector
// leaves them behind as dead @ORM text; drop them so the tree greps clean.
$dead = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/next/src", FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') continue;
    $s = (string) file_get_contents($f->getPathname());
    if (!str_contains($s, '@ORM\\')) continue;
    // a /** ... @ORM ... */ block followed by blank line(s) then another /** is dead
    $out = preg_replace(
        '#[ \t]*/\*\*(?:(?!\*/).)*?@ORM\\\\(?:(?!\*/).)*?\*+/\s*\n(\s*\n)+(?=[ \t]*/\*\*)#s',
        '',
        $s
    );
    if ($out !== null && $out !== $s) { file_put_contents($f->getPathname(), $out); $dead++; }
}

printf("fixups: %d applied, %d already clean; %d dead docblocks removed\n", $applied, $skipped, $dead);
