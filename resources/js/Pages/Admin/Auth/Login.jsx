import LoginForm from '../../../Components/Auth/LoginForm';

export default function Login() {
    return (
        <LoginForm
            title="Connexion Administration"
            badgeIcon="💼"
            headingTitle="Dealtoo Billing B2B"
            headingSubtitle="Espace d'administration et supervision financière"
            emailLabel="Adresse Email Administrateur"
            emailPlaceholder="admin@dughu-dealtoo.com"
            submitButtonText="Accéder au tableau de bord"
            actionUrl="/admin/login"
            defaultEmail="admin@dughu-dealtoo.com"
            defaultPassword="password"
            helperText={
                <>
                    Identifiants par défaut : <code>admin@dughu-dealtoo.com</code> / <code>password</code>
                </>
            }
        />
    );
}
