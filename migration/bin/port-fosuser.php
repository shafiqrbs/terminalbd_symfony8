<?php

declare(strict_types=1);

/**
 * The FOSUserBundle references left in ported files.
 *
 * The bundle has no Symfony 4+ release. Its runtime is replaced by
 * hand-written classes (see migration/hand-written.txt); this handles the
 * files that are still mechanically ported and therefore regenerated on every
 * pipeline run:
 *
 *   UserType             extended FOS's RegistrationFormType, but overrides
 *                        buildForm() without calling the parent, so the base
 *                        class only supplied data_class - now AbstractType.
 *   UserProfileFormType  extended FOS's ProfileFormType and overrides
 *                        buildUserForm(); repointed at the app's own base.
 *   ManageUserController dispatched three FOSUserEvents that nothing in
 *                        either tree subscribes to - they existed for
 *                        FOSUserBundle's own listeners.
 *   DomainUser controllers imported FOS's UserManager as a type hint.
 *
 * Idempotent. Usage: php8.5 migration/bin/port-fosuser.php [--dry-run]
 */

$root = dirname(__DIR__, 2) . '/next';
$dryRun = in_array('--dry-run', $argv, true);

$stats = ['files' => 0, 'edits' => 0];

$edit = static function (string $relative, callable $transform) use ($root, $dryRun, &$stats): void {
    $path = $root . '/' . $relative;

    if (!is_file($path)) {
        return;
    }

    $code = file_get_contents($path);
    $updated = $transform($code);

    if ($updated === $code) {
        return;
    }

    ++$stats['files'];

    if (!$dryRun) {
        file_put_contents($path, $updated);
    }
};

// ---- form types ------------------------------------------------------------

$edit('src/User/Form/UserType.php', static function (string $code) use (&$stats): string {
    $code = str_replace(
        'use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;',
        'use Symfony\Component\Form\AbstractType;',
        $code,
        $count,
    );
    $stats['edits'] += $count;

    return str_replace('class UserType extends BaseType', 'class UserType extends AbstractType', $code);
});

$edit('src/User/Form/Type/UserProfileFormType.php', static function (string $code) use (&$stats): string {
    $code = str_replace(
        'use FOS\UserBundle\Form\Type\ProfileFormType as BaseProfileFormType;',
        'use App\User\Form\Type\AbstractProfileFormType as BaseProfileFormType;',
        $code,
        $count,
    );
    $stats['edits'] += $count;

    $code = str_replace(
        'protected function buildUserForm(FormBuilderInterface $builder, array $options)' . "\n",
        'protected function buildUserForm(FormBuilderInterface $builder, array $options): void' . "\n",
        $code,
    );

    return preg_replace(
        '/public function getName\(\)(\s*\n\s*\{)/',
        'public function getBlockPrefix(): string$1',
        $code,
    );
});

// ---- controllers -----------------------------------------------------------

$edit('src/User/Controller/ManageUserController.php', static function (string $code) use (&$stats): string {
    $code = str_replace(
        "use FOS\UserBundle\Event\FilterUserResponseEvent;\nuse FOS\UserBundle\Event\UserEvent;\nuse FOS\UserBundle\FOSUserEvents;\nuse JMS\SecurityExtraBundle\Annotation as JMS;\n",
        "use App\User\Manager\UserManager;\nuse Symfony\Component\Security\Http\Attribute\IsGranted;\n",
        $code,
        $count,
    );
    $stats['edits'] += $count;

    $code = str_replace(
        "        private readonly PaginatorInterface \$paginator,\n    ) {",
        "        private readonly PaginatorInterface \$paginator,\n        private readonly UserManager \$userManager,\n    ) {",
        $code,
    );

    $code = preg_replace(
        '/\tprivate function getUserManager\(\)\s*\n\t\{\s*\n(?:[^\n]*\n)?\t\treturn \$this->get\(\'Core\.user_manager\'\);\s*\n\t\}/',
        "\tprivate function getUserManager(): UserManager\n\t{\n\t\treturn \$this->userManager;\n\t}",
        $code,
    );

    // Dispatches to listeners that no longer exist and never had subscribers.
    $code = preg_replace(
        '/\n[ \t]*\$this->getEventDispatcher\(\)->dispatch\(\s*(?:DocudexUserEvent|FOSUserEvents)::[A-Z_]+,\s*new \w+\([^;]*?\);\n/s',
        "\n",
        $code,
    );

    return preg_replace(
        '/\n[ \t]*\/\*\*\n[ \t]*\* @JMS\\\\Secure\(roles="([A-Z_]+)"\)\n[ \t]*\*\/\n/',
        "\n    #[IsGranted('$1')]\n",
        $code,
    );
});

foreach ([
    'src/DomainUser/Controller/AgentController.php',
    'src/DomainUser/Controller/AgentPaymentController.php',
    'src/DomainUser/Controller/DomainUserController.php',
] as $relative) {
    $edit($relative, static function (string $code) use (&$stats): string {
        $code = str_replace(
            'use FOS\UserBundle\Model\UserManager;',
            'use App\User\Manager\UserManager;',
            $code,
            $count,
        );
        $stats['edits'] += $count;

        return $code;
    });
}

printf("files rewritten: %d\nFOS references removed: %d\n", $stats['files'], $stats['edits']);
