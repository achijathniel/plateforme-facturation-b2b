import LoginForm from '../../../Components/Auth/LoginForm';

export default function Login() {
    return (
        <LoginForm
            title="Connexion Portail Entreprise"
            badgeIcon="🏢"
            headingTitle="Portail Entreprise B2B"
            headingSubtitle="Espace comptabilité et gestion des factures de votre entreprise"
            emailLabel="Adresse Email Professionnelle"
            emailPlaceholder="comptable0@dealtoo.com"
            submitButtonText="Accéder à mon espace entreprise"
            actionUrl="/portal/login"
            defaultEmail="comptable0@dealtoo.com"
            defaultPassword="password"
            helperText={
                <>
                    Identifiants de test : <code>comptable0@dealtoo.com</code> / <code>password</code>
                </>
            }
        />
    );
}
