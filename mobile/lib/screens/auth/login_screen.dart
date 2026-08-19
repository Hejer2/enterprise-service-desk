import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/error_handler.dart';
import '../../core/widgets/app_button.dart';
import '../../core/widgets/app_input.dart';
import '../../repositories/auth_repository.dart';
import '../../services/api_client.dart';
import '../../models/user.dart';

final authRepositoryProvider =
    Provider((ref) => AuthRepository(ref.read(apiClientProvider)));
final authProvider = StateProvider<bool>((ref) => false);
final currentUserProvider = StateProvider<User?>((ref) => null);

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _emailController.addListener(_clearError);
    _passwordController.addListener(_clearError);
  }

  @override
  void dispose() {
    _emailController.removeListener(_clearError);
    _passwordController.removeListener(_clearError);
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _clearError() {
    if (_errorMessage != null) {
      setState(() {
        _errorMessage = null;
      });
    }
  }

  Future<void> _handleLogin() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty) {
      setState(() {
        _errorMessage = "Please enter your email.";
      });
      return;
    }

    if (!email.contains('@') || !email.contains('.')) {
      setState(() {
        _errorMessage = "Please enter a valid email address.";
      });
      return;
    }

    if (password.isEmpty) {
      setState(() {
        _errorMessage = "Please enter your password.";
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final repo = ref.read(authRepositoryProvider);
      final user = await repo.login(email, password);
      if (user != null) {
        ref.read(currentUserProvider.notifier).state = user;
        ref.read(authProvider.notifier).state = true;
        if (mounted) context.go('/dashboard');
      } else {
        setState(() {
          _errorMessage = "Incorrect email or password.";
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = AppErrorHandler.getReadableErrorMessage(
          e,
          defaultMessage: "Incorrect email or password.",
        );
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isCompact = screenWidth < 480;
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? AppColors.darkBgApp : AppColors.bgApp,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: EdgeInsets.symmetric(
              horizontal: isCompact ? AppSpacing.md : AppSpacing.lg,
              vertical: isCompact ? AppSpacing.md : AppSpacing.lg,
            ),
            child: Container(
              width: double.infinity,
              constraints: const BoxConstraints(maxWidth: 420),
              padding:
                  EdgeInsets.all(isCompact ? AppSpacing.lg : AppSpacing.xl),
              decoration: BoxDecoration(
                color: isDark ? AppColors.darkSurface : AppColors.surface,
                border: Border.all(
                    color: isDark ? AppColors.darkBorder : AppColors.border),
                borderRadius: BorderRadius.circular(AppRadius.card),
                boxShadow: AppShadows.medium,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(AppSpacing.sm),
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius: BorderRadius.circular(AppRadius.input),
                        ),
                        child: const Icon(Icons.shield_outlined,
                            color: AppColors.primary, size: 28),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Text(
                        'Service Desk',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: isDark
                              ? AppColors.darkTextPrimary
                              : AppColors.textPrimary,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.xl),
                  if (_errorMessage != null) ...[
                    Container(
                      padding: const EdgeInsets.all(AppSpacing.md),
                      decoration: BoxDecoration(
                        color: AppColors.dangerLight,
                        borderRadius: BorderRadius.circular(AppRadius.input),
                        border: Border.all(
                            color: AppColors.danger.withOpacity(0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline,
                              color: AppColors.danger, size: 18),
                          const SizedBox(width: AppSpacing.sm),
                          Expanded(
                            child: Text(
                              _errorMessage!,
                              style: const TextStyle(
                                  color: AppColors.danger,
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: AppSpacing.md),
                  ],
                  AppInput(
                    label: 'EMAIL ADDRESS',
                    placeholder: 'name@company.com',
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    leadingIcon: const Icon(Icons.email_outlined,
                        size: 20, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  AppInput(
                    label: 'PASSWORD',
                    placeholder: '••••••••',
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    leadingIcon: const Icon(Icons.lock_outline,
                        size: 20, color: AppColors.textSecondary),
                    trailingIcon: IconButton(
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility_off_outlined
                            : Icons.visibility_outlined,
                        color: AppColors.textSecondary,
                        size: 20,
                      ),
                      onPressed: () =>
                          setState(() => _obscurePassword = !_obscurePassword),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xl),
                  AppButton(
                    text: 'Sign In',
                    isLoading: _isLoading,
                    isFullWidth: true,
                    onPressed: _handleLogin,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
