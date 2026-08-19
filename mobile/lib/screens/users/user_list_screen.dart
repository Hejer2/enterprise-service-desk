import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/empty_state.dart';
import '../../services/api_client.dart';
import '../../repositories/user_repository.dart';

final userRepositoryProvider =
    Provider((ref) => UserRepository(ref.read(apiClientProvider)));

final usersProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  return ref.read(userRepositoryProvider).getUsers();
});

class UserListScreen extends ConsumerWidget {
  const UserListScreen({super.key});

  void _showUserDetailsSheet(
      BuildContext context, WidgetRef ref, Map<String, dynamic> u) {
    final id = (u['id'] as num).toInt();
    final fullName = u['fullName']?.toString() ?? 'Unknown User';
    final email = u['email']?.toString() ?? '';
    final phone = u['phone']?.toString() ?? 'N/A';
    final role = u['role']?.toString() ?? 'User';
    final language = u['language']?.toString() ?? 'EN';
    final userTheme = u['theme']?.toString() ?? 'System';
    final initial = fullName.isNotEmpty ? fullName.substring(0, 1) : 'U';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Modal Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('USER PROFILE DETAILS',
                      style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey,
                          letterSpacing: 0.8)),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, size: 20),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Profile Card Header
              Row(
                children: [
                  CircleAvatar(
                    radius: 30,
                    backgroundColor: const Color(0xFF6366F1).withOpacity(0.15),
                    child: Text(
                      initial,
                      style: const TextStyle(
                        color: Color(0xFF6366F1),
                        fontWeight: FontWeight.bold,
                        fontSize: 22,
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(fullName,
                            style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF0F172A))),
                        const SizedBox(height: 2),
                        Text(email,
                            style: TextStyle(
                                color: Colors.grey.shade600, fontSize: 13)),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFF6366F1).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            role,
                            style: const TextStyle(
                              color: Color(0xFF6366F1),
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              const Divider(),
              const SizedBox(height: 16),

              // Details Tiles
              _buildDetailRow(Icons.phone_outlined, 'Phone Number', phone),
              const SizedBox(height: 12),
              _buildDetailRow(
                  Icons.language_outlined, 'Preferred Language', language),
              const SizedBox(height: 12),
              _buildDetailRow(
                  Icons.palette_outlined, 'Display Theme', userTheme),
              const SizedBox(height: 24),

              // Actions Buttons Row (Edit / Delete)
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _showEditUserDialog(context, ref, u);
                      },
                      icon: const Icon(Icons.edit, size: 16),
                      label: const Text('✏️ Edit User',
                          style: TextStyle(fontWeight: FontWeight.bold)),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _confirmDeleteUser(context, ref, id, fullName);
                      },
                      icon: const Icon(Icons.delete, size: 16),
                      label: const Text('🗑️ Delete',
                          style: TextStyle(fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFEF4444),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 18, color: const Color(0xFF6366F1)),
          const SizedBox(width: 10),
          Text(label,
              style: const TextStyle(
                  fontSize: 13,
                  color: Colors.grey,
                  fontWeight: FontWeight.w500)),
          const Spacer(),
          Text(value,
              style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A))),
        ],
      ),
    );
  }

  void _showEditUserDialog(
      BuildContext context, WidgetRef ref, Map<String, dynamic> u) {
    final id = (u['id'] as num).toInt();
    final firstNameController =
        TextEditingController(text: u['firstName']?.toString() ?? '');
    final lastNameController =
        TextEditingController(text: u['lastName']?.toString() ?? '');
    final emailController =
        TextEditingController(text: u['email']?.toString() ?? '');
    final phoneController =
        TextEditingController(text: u['phone']?.toString() ?? '');
    final passwordController = TextEditingController();
    String selectedRole = u['role']?.toString() ?? 'ROLE_EMPLOYEE';

    final roles = [
      'ROLE_EMPLOYEE',
      'ROLE_ADMIN',
      'ROLE_IT_TECH',
      'ROLE_MAINTENANCE_TECH',
      'ROLE_HR'
    ];

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              const Icon(Icons.edit, color: Color(0xFF6366F1), size: 20),
              const SizedBox(width: 8),
              Text('Edit User #${u['id']}',
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, fontSize: 18)),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextField(
                  controller: firstNameController,
                  decoration: const InputDecoration(
                      labelText: 'First Name', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: lastNameController,
                  decoration: const InputDecoration(
                      labelText: 'Last Name', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: emailController,
                  decoration: const InputDecoration(
                      labelText: 'Email Address', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: phoneController,
                  decoration: const InputDecoration(
                      labelText: 'Phone Number', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value:
                      roles.contains(selectedRole) ? selectedRole : roles.first,
                  decoration: const InputDecoration(
                      labelText: 'System Role', border: OutlineInputBorder()),
                  items: roles
                      .map((r) => DropdownMenuItem(
                          value: r,
                          child: Text(r, style: const TextStyle(fontSize: 13))))
                      .toList(),
                  onChanged: (val) {
                    if (val != null) setDialogState(() => selectedRole = val);
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                      labelText: 'New Password (Optional)',
                      border: OutlineInputBorder()),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () async {
                final repo = ref.read(userRepositoryProvider);
                final success = await repo.updateUser(id, {
                  'firstName': firstNameController.text.trim(),
                  'lastName': lastNameController.text.trim(),
                  'email': emailController.text.trim(),
                  'phone': phoneController.text.trim(),
                  'role': selectedRole,
                  if (passwordController.text.isNotEmpty)
                    'password': passwordController.text,
                });

                if (context.mounted) {
                  Navigator.pop(context);
                  if (success) {
                    ref.invalidate(usersProvider);
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                          content: Text('User details updated successfully!'),
                          backgroundColor: Colors.green),
                    );
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                          content: Text('Failed to update user.'),
                          backgroundColor: Colors.red),
                    );
                  }
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
              ),
              child: const Text('Save Changes'),
            ),
          ],
        ),
      ),
    );
  }

  void _showAddUserDialog(BuildContext context, WidgetRef ref) {
    final firstNameController = TextEditingController();
    final lastNameController = TextEditingController();
    final emailController = TextEditingController();
    final phoneController = TextEditingController();
    final passwordController = TextEditingController(text: 'Password123!');
    String selectedRole = 'ROLE_EMPLOYEE';

    final roles = [
      'ROLE_EMPLOYEE',
      'ROLE_ADMIN',
      'ROLE_IT_TECH',
      'ROLE_MAINTENANCE_TECH',
      'ROLE_HR'
    ];

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Row(
            children: [
              Icon(Icons.person_add, color: Color(0xFF6366F1), size: 20),
              SizedBox(width: 8),
              Text('Add New User',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
            ],
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextField(
                  controller: firstNameController,
                  decoration: const InputDecoration(
                      labelText: 'First Name', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: lastNameController,
                  decoration: const InputDecoration(
                      labelText: 'Last Name', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: emailController,
                  decoration: const InputDecoration(
                      labelText: 'Email Address', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: phoneController,
                  decoration: const InputDecoration(
                      labelText: 'Phone Number', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: selectedRole,
                  decoration: const InputDecoration(
                      labelText: 'System Role', border: OutlineInputBorder()),
                  items: roles
                      .map((r) => DropdownMenuItem(
                          value: r,
                          child: Text(r, style: const TextStyle(fontSize: 13))))
                      .toList(),
                  onChanged: (val) {
                    if (val != null) setDialogState(() => selectedRole = val);
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                      labelText: 'Password', border: OutlineInputBorder()),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () async {
                if (emailController.text.trim().isEmpty ||
                    firstNameController.text.trim().isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                        content: Text('Please fill in required fields.')),
                  );
                  return;
                }

                final repo = ref.read(userRepositoryProvider);
                final success = await repo.createUser({
                  'firstName': firstNameController.text.trim(),
                  'lastName': lastNameController.text.trim(),
                  'email': emailController.text.trim(),
                  'phone': phoneController.text.trim(),
                  'role': selectedRole,
                  'password': passwordController.text,
                });

                if (context.mounted) {
                  Navigator.pop(context);
                  if (success) {
                    ref.invalidate(usersProvider);
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                          content: Text('New user created successfully!'),
                          backgroundColor: Colors.green),
                    );
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                          content: Text('Failed to create user.'),
                          backgroundColor: Colors.red),
                    );
                  }
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
              ),
              child: const Text('Create User'),
            ),
          ],
        ),
      ),
    );
  }

  void _confirmDeleteUser(
      BuildContext context, WidgetRef ref, int id, String fullName) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Row(
          children: [
            Icon(Icons.warning, color: Colors.red, size: 22),
            SizedBox(width: 8),
            Text('Confirm Deletion',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          ],
        ),
        content: Text(
            'Are you sure you want to delete user "$fullName"? This action cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              final repo = ref.read(userRepositoryProvider);
              final success = await repo.deleteUser(id);

              if (context.mounted) {
                Navigator.pop(context);
                if (success) {
                  ref.invalidate(usersProvider);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                        content: Text('User "$fullName" deleted successfully.'),
                        backgroundColor: Colors.green),
                  );
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                        content: Text('Failed to delete user.'),
                        backgroundColor: Colors.red),
                  );
                }
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
              foregroundColor: Colors.white,
            ),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final usersAsync = ref.watch(usersProvider);
    final isDesktop = MediaQuery.of(context).size.width >= 768;

    return SingleChildScrollView(
      padding: EdgeInsets.only(
        left: isDesktop ? 24 : 14,
        right: isDesktop ? 24 : 14,
        top: isDesktop ? 24 : 14,
        bottom: 24,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Manage Users',
                      style: TextStyle(
                        fontSize: isDesktop ? 30 : 22,
                        fontWeight: FontWeight.w800,
                        color: theme.colorScheme.onSurface,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'System employees & roles.',
                      style: TextStyle(
                        color: theme.colorScheme.onSurface.withOpacity(0.6),
                        fontSize: isDesktop ? 15 : 13,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
              ElevatedButton.icon(
                onPressed: () => _showAddUserDialog(context, ref),
                icon: const Icon(Icons.add, size: 18),
                label: Text(isDesktop ? 'Add New User' : 'Add'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6366F1),
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.symmetric(
                    horizontal: isDesktop ? 20 : 14,
                    vertical: isDesktop ? 14 : 10,
                  ),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14)),
                  textStyle: const TextStyle(
                      fontWeight: FontWeight.bold, fontSize: 14),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          usersAsync.when(
            data: (users) {
              if (users.isEmpty) {
                return const EmptyState(
                  title: 'No users found',
                  description:
                      'There are currently no registered users matching your criteria.',
                  icon: Icons.people_outline,
                );
              }

              // On Mobile: Render Mobile Cards
              if (!isDesktop) {
                return ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: users.length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final u = users[index];
                    return _buildMobileUserCard(context, ref, u, theme);
                  },
                );
              }

              // On Desktop: Render DataTable
              return AppCard(
                padding: const EdgeInsets.all(0),
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: DataTable(
                    headingTextStyle: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 13,
                        color: Colors.grey),
                    dataTextStyle: TextStyle(
                        fontSize: 14, color: theme.colorScheme.onSurface),
                    columnSpacing: 32,
                    showCheckboxColumn: false,
                    columns: const [
                      DataColumn(label: Text('FULL NAME')),
                      DataColumn(label: Text('EMAIL ADDRESS')),
                      DataColumn(label: Text('ROLE')),
                      DataColumn(label: Text('LANGUAGE')),
                      DataColumn(label: Text('THEME')),
                      DataColumn(label: Text('ACTIONS')),
                    ],
                    rows: users.map((u) {
                      final id = (u['id'] as num).toInt();
                      final fullName =
                          u['fullName']?.toString() ?? 'Unknown User';

                      return DataRow(
                        onSelectChanged: (_) =>
                            _showUserDetailsSheet(context, ref, u),
                        cells: [
                          DataCell(Text(fullName,
                              style: const TextStyle(
                                  fontWeight: FontWeight.w600))),
                          DataCell(Text(u['email']?.toString() ?? '')),
                          DataCell(
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFF6366F1).withOpacity(0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(u['role']?.toString() ?? 'User',
                                  style: const TextStyle(
                                      color: Color(0xFF6366F1),
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold)),
                            ),
                          ),
                          DataCell(Text(u['language']?.toString() ?? 'EN')),
                          DataCell(Text(u['theme']?.toString() ?? 'System')),
                          DataCell(Row(
                            children: [
                              TextButton.icon(
                                onPressed: () =>
                                    _showEditUserDialog(context, ref, u),
                                icon: const Icon(Icons.edit, size: 14),
                                label: const Text('Edit'),
                                style: TextButton.styleFrom(
                                  backgroundColor: theme.colorScheme.surface,
                                  foregroundColor: theme.colorScheme.onSurface,
                                  side: BorderSide(
                                      color: theme.colorScheme.onSurface
                                          .withOpacity(0.2)),
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 12, vertical: 6),
                                ),
                              ),
                              const SizedBox(width: 8),
                              TextButton.icon(
                                onPressed: () => _confirmDeleteUser(
                                    context, ref, id, fullName),
                                icon: const Icon(Icons.delete, size: 14),
                                label: const Text('Delete'),
                                style: TextButton.styleFrom(
                                  backgroundColor: const Color(0xFFEF4444),
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 12, vertical: 6),
                                ),
                              ),
                            ],
                          )),
                        ],
                      );
                    }).toList(),
                  ),
                ),
              );
            },
            loading: () => const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator())),
            error: (err, stack) => Padding(
                padding: const EdgeInsets.all(40),
                child: Center(child: Text('Error: $err'))),
          ),
        ],
      ),
    );
  }

  Widget _buildMobileUserCard(BuildContext context, WidgetRef ref,
      Map<String, dynamic> u, ThemeData theme) {
    final fullName = u['fullName']?.toString() ?? 'Unknown User';
    final email = u['email']?.toString() ?? '';
    final role = u['role']?.toString() ?? 'User';
    final initial = fullName.isNotEmpty ? fullName.substring(0, 1) : 'U';

    return InkWell(
      onTap: () => _showUserDetailsSheet(context, ref, u),
      borderRadius: BorderRadius.circular(14),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            CircleAvatar(
              radius: 20,
              backgroundColor: const Color(0xFF6366F1).withOpacity(0.15),
              child: Text(
                initial,
                style: const TextStyle(
                  color: Color(0xFF6366F1),
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(fullName,
                      style: const TextStyle(
                          fontWeight: FontWeight.bold, fontSize: 14.5)),
                  const SizedBox(height: 2),
                  Text(email,
                      style:
                          TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF6366F1).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                role,
                style: const TextStyle(
                  color: Color(0xFF6366F1),
                  fontSize: 10.5,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
