import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/skeleton_loader.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_state.dart';
import '../../models/ticket.dart';
import '../../repositories/ticket_repository.dart';
import '../../services/api_client.dart';
import 'ticket_detail_screen.dart';

final ticketRepositoryProvider =
    Provider((ref) => TicketRepository(ref.read(apiClientProvider)));

final ticketsProvider = FutureProvider.autoDispose<List<Ticket>>((ref) async {
  final repo = ref.read(ticketRepositoryProvider);
  return repo.getTickets();
});

class TicketListScreen extends ConsumerStatefulWidget {
  const TicketListScreen({super.key});

  @override
  ConsumerState<TicketListScreen> createState() => _TicketListScreenState();
}

class _TicketListScreenState extends ConsumerState<TicketListScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedStatus = '';
  String _selectedPriority = '';
  String _selectedCategory = '';

  final List<String> _statuses = [
    'All Statuses',
    'Open',
    'Assigned',
    'In Progress',
    'Waiting for Employee',
    'Waiting for Technician',
    'Approved',
    'Rejected',
    'Resolved',
    'Closed',
    'Cancelled'
  ];

  final List<String> _priorities = [
    'All Priorities',
    'Low',
    'Medium',
    'High',
    'Critical'
  ];

  final List<String> _categories = [
    'All Categories',
    'IT Support',
    'Machine Maintenance',
    'Leave Request',
    'General Request',
    'Incident',
    'Suggestion'
  ];

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _clearFilters() {
    setState(() {
      _searchController.clear();
      _searchQuery = '';
      _selectedStatus = '';
      _selectedPriority = '';
      _selectedCategory = '';
    });
  }

  int get _activeFiltersCount {
    int count = 0;
    if (_selectedStatus.isNotEmpty) count++;
    if (_selectedPriority.isNotEmpty) count++;
    if (_selectedCategory.isNotEmpty) count++;
    return count;
  }

  void _openTicketDetails(Ticket ticket) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => TicketDetailScreen(ticket: ticket),
      ),
    );
  }

  void _showCreateTicketDialog(BuildContext context) {
    final titleController = TextEditingController();
    final descController = TextEditingController();
    String category = 'IT Support';
    String priority = 'Medium';
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) {
          return Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(context).viewInsets.bottom,
            ),
            child: Container(
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
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Create New Ticket',
                            style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0F172A))),
                        IconButton(
                          onPressed: () => Navigator.pop(context),
                          icon: const Icon(Icons.close, size: 20),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    const Text('TITLE',
                        style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: titleController,
                      decoration: InputDecoration(
                        hintText: 'Brief summary of issue...',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide:
                                const BorderSide(color: Color(0xFFE2E8F0))),
                      ),
                    ),
                    const SizedBox(height: 14),
                    const Text('CATEGORY',
                        style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: category,
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide:
                                const BorderSide(color: Color(0xFFE2E8F0))),
                      ),
                      items: const [
                        DropdownMenuItem(
                            value: 'IT Support', child: Text('IT Support')),
                        DropdownMenuItem(
                            value: 'Machine Maintenance',
                            child: Text('Machine Maintenance')),
                        DropdownMenuItem(
                            value: 'Leave Request',
                            child: Text('Leave Request')),
                        DropdownMenuItem(
                            value: 'General Request',
                            child: Text('General Request')),
                      ],
                      onChanged: (val) {
                        if (val != null) setModalState(() => category = val);
                      },
                    ),
                    const SizedBox(height: 14),
                    const Text('PRIORITY',
                        style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey)),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: priority,
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide:
                                const BorderSide(color: Color(0xFFE2E8F0))),
                      ),
                      items: const [
                        DropdownMenuItem(
                            value: 'Low', child: Text('Low')),
                        DropdownMenuItem(
                            value: 'Medium', child: Text('Medium')),
                        DropdownMenuItem(
                            value: 'High', child: Text('High')),
                        DropdownMenuItem(
                            value: 'Critical', child: Text('Critical')),
                      ],
                      onChanged: (val) {
                        if (val != null) setModalState(() => priority = val);
                      },
                    ),
                    const SizedBox(height: 14),
                    const Text('DESCRIPTION',
                        style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: descController,
                      maxLines: 3,
                      decoration: InputDecoration(
                        hintText: 'Detailed explanation...',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide:
                                const BorderSide(color: Color(0xFFE2E8F0))),
                      ),
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final title = titleController.text.trim();
                                final desc = descController.text.trim();
                                if (title.isEmpty || desc.isEmpty) return;

                                final navigator = Navigator.of(context);
                                final messenger = ScaffoldMessenger.of(context);

                                setModalState(() => isSubmitting = true);
                                final repo = ref.read(ticketRepositoryProvider);
                                final success = await repo.createTicket(
                                  title: title,
                                  description: desc,
                                  category: category,
                                  priority: priority,
                                );
                                if (mounted) {
                                  navigator.pop();
                                  ref.invalidate(ticketsProvider);
                                  messenger.showSnackBar(
                                    SnackBar(
                                      content: Text(success
                                          ? 'Ticket created successfully!'
                                          : 'Failed to create ticket'),
                                      backgroundColor: success
                                          ? const Color(0xFF10B981)
                                          : const Color(0xFFEF4444),
                                    ),
                                  );
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF6366F1),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12)),
                        ),
                        child: isSubmitting
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                    strokeWidth: 2, color: Colors.white),
                              )
                            : const Text('Submit Ticket',
                                style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 15)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  void _showFilterBottomSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) {
          return Container(
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
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Filter Tickets',
                          style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF0F172A))),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close, size: 20),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Status Dropdown
                  const Text('STATUS',
                      style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    value: _selectedStatus.isEmpty
                        ? 'All Statuses'
                        : _selectedStatus,
                    isExpanded: true,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide:
                              const BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                    items: _statuses
                        .map((st) => DropdownMenuItem(
                            value: st,
                            child:
                                Text(st, style: const TextStyle(fontSize: 14))))
                        .toList(),
                    onChanged: (val) {
                      setModalState(() {});
                      setState(() => _selectedStatus =
                          (val == 'All Statuses') ? '' : (val ?? ''));
                    },
                  ),
                  const SizedBox(height: 16),

                  // Priority Dropdown
                  const Text('PRIORITY',
                      style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    value: _selectedPriority.isEmpty
                        ? 'All Priorities'
                        : _selectedPriority,
                    isExpanded: true,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide:
                              const BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                    items: _priorities
                        .map((pr) => DropdownMenuItem(
                            value: pr,
                            child:
                                Text(pr, style: const TextStyle(fontSize: 14))))
                        .toList(),
                    onChanged: (val) {
                      setModalState(() {});
                      setState(() => _selectedPriority =
                          (val == 'All Priorities') ? '' : (val ?? ''));
                    },
                  ),
                  const SizedBox(height: 16),

                  // Category Dropdown
                  const Text('CATEGORY',
                      style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    value: _selectedCategory.isEmpty
                        ? 'All Categories'
                        : _selectedCategory,
                    isExpanded: true,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      contentPadding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide:
                              const BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                    items: _categories
                        .map((cat) => DropdownMenuItem(
                            value: cat,
                            child: Text(cat,
                                style: const TextStyle(fontSize: 14))))
                        .toList(),
                    onChanged: (val) {
                      setModalState(() {});
                      setState(() => _selectedCategory =
                          (val == 'All Categories') ? '' : (val ?? ''));
                    },
                  ),
                  const SizedBox(height: 24),

                  // Action Buttons
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {
                            _clearFilters();
                            setModalState(() {});
                          },
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12)),
                          ),
                          child: const Text('Reset All',
                              style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () => Navigator.pop(context),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF6366F1),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12)),
                          ),
                          child: const Text('Apply Filters',
                              style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    try {
      final ticketsAsync = ref.watch(ticketsProvider);
      final theme = Theme.of(context);
      final isDesktop = MediaQuery.of(context).size.width >= 768;

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(ticketsProvider);
      },
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.only(
          left: isDesktop ? 24 : 16,
          right: isDesktop ? 24 : 16,
          top: isDesktop ? 24 : 16,
          bottom: 24,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
          // Header
          Wrap(
            alignment: WrapAlignment.spaceBetween,
            crossAxisAlignment: WrapCrossAlignment.center,
            spacing: 12,
            runSpacing: 12,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Service Desk Tickets',
                    style: TextStyle(
                      fontSize: isDesktop ? 30 : 22,
                      fontWeight: FontWeight.w800,
                      color: theme.colorScheme.onSurface,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Browse, filter, and track support tickets.',
                    style: TextStyle(
                      color: theme.colorScheme.onSurface.withOpacity(0.6),
                      fontSize: isDesktop ? 15 : 13,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
              ElevatedButton.icon(
                onPressed: () => _showCreateTicketDialog(context),
                icon: const Icon(Icons.add, size: 18),
                label: Text(isDesktop ? 'Create Ticket' : 'New'),
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

          // Search & Filter Bar
          AppCard(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (!isDesktop) ...[
                  // Mobile View: Search Input + Filter Modal Button
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _searchController,
                          onChanged: (val) =>
                              setState(() => _searchQuery = val.toLowerCase()),
                          decoration: InputDecoration(
                            hintText: 'Number, title, description...',
                            hintStyle: TextStyle(
                                color: Colors.grey.shade400, fontSize: 13),
                            prefixIcon: const Icon(Icons.search,
                                size: 18, color: Color(0xFF6366F1)),
                            filled: true,
                            fillColor: theme.colorScheme.surface,
                            contentPadding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 10),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(
                                  color: Colors.grey.withOpacity(0.2)),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton.icon(
                        onPressed: () => _showFilterBottomSheet(context),
                        icon: const Icon(Icons.tune, size: 18),
                        label: Text(_activeFiltersCount > 0
                            ? 'Filter ($_activeFiltersCount)'
                            : 'Filter'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _activeFiltersCount > 0
                              ? const Color(0xFF6366F1)
                              : theme.colorScheme.surface,
                          foregroundColor: _activeFiltersCount > 0
                              ? Colors.white
                              : theme.colorScheme.onSurface,
                          side: BorderSide(
                              color: _activeFiltersCount > 0
                                  ? const Color(0xFF6366F1)
                                  : theme.colorScheme.onSurface
                                      .withOpacity(0.2)),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 14, vertical: 12),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12)),
                          textStyle: const TextStyle(
                              fontWeight: FontWeight.bold, fontSize: 13),
                        ),
                      ),
                    ],
                  ),

                  // Active Filter Badges
                  if (_activeFiltersCount > 0 || _searchQuery.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          if (_selectedStatus.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(right: 6),
                              child: Chip(
                                label: Text('Status: $_selectedStatus',
                                    style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold)),
                                backgroundColor:
                                    const Color(0xFF6366F1).withOpacity(0.1),
                                deleteIcon: const Icon(Icons.close, size: 14),
                                onDeleted: () =>
                                    setState(() => _selectedStatus = ''),
                              ),
                            ),
                          if (_selectedPriority.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(right: 6),
                              child: Chip(
                                label: Text('Priority: $_selectedPriority',
                                    style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold)),
                                backgroundColor:
                                    const Color(0xFF6366F1).withOpacity(0.1),
                                deleteIcon: const Icon(Icons.close, size: 14),
                                onDeleted: () =>
                                    setState(() => _selectedPriority = ''),
                              ),
                            ),
                          if (_selectedCategory.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(right: 6),
                              child: Chip(
                                label: Text('Category: $_selectedCategory',
                                    style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold)),
                                backgroundColor:
                                    const Color(0xFF6366F1).withOpacity(0.1),
                                deleteIcon: const Icon(Icons.close, size: 14),
                                onDeleted: () =>
                                    setState(() => _selectedCategory = ''),
                              ),
                            ),
                          TextButton(
                            onPressed: _clearFilters,
                            style:
                                TextButton.styleFrom(padding: EdgeInsets.zero),
                            child: const Text('Clear All',
                                style:
                                    TextStyle(fontSize: 12, color: Colors.red)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ] else ...[
                  // Desktop View: Row of All Filters
                  Wrap(
                    spacing: 12,
                    runSpacing: 12,
                    crossAxisAlignment: WrapCrossAlignment.end,
                    children: [
                      SizedBox(
                        width: 220,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('SEARCH KEYWORDS',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.grey)),
                            const SizedBox(height: 6),
                            TextField(
                              controller: _searchController,
                              onChanged: (val) => setState(
                                  () => _searchQuery = val.toLowerCase()),
                              decoration: InputDecoration(
                                hintText: 'Number, title, description...',
                                hintStyle: TextStyle(
                                    color: Colors.grey.shade400, fontSize: 13),
                                prefixIcon: const Icon(Icons.search,
                                    size: 18, color: Color(0xFF6366F1)),
                                filled: true,
                                fillColor: theme.colorScheme.surface,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 14, vertical: 10),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: BorderSide(
                                      color: Colors.grey.withOpacity(0.2)),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      SizedBox(
                        width: 160,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('STATUS',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.grey)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<String>(
                              value: _selectedStatus.isEmpty
                                  ? 'All Statuses'
                                  : _selectedStatus,
                              isExpanded: true,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor: theme.colorScheme.surface,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 10),
                                border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide: BorderSide(
                                        color: Colors.grey.withOpacity(0.2))),
                              ),
                              items: _statuses
                                  .map((st) => DropdownMenuItem(
                                      value: st,
                                      child: Text(st,
                                          style: const TextStyle(fontSize: 13),
                                          overflow: TextOverflow.ellipsis)))
                                  .toList(),
                              onChanged: (val) => setState(() =>
                                  _selectedStatus = (val == 'All Statuses')
                                      ? ''
                                      : (val ?? '')),
                            ),
                          ],
                        ),
                      ),
                      SizedBox(
                        width: 150,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('PRIORITY',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.grey)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<String>(
                              value: _selectedPriority.isEmpty
                                  ? 'All Priorities'
                                  : _selectedPriority,
                              isExpanded: true,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor: theme.colorScheme.surface,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 10),
                                border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide: BorderSide(
                                        color: Colors.grey.withOpacity(0.2))),
                              ),
                              items: _priorities
                                  .map((pr) => DropdownMenuItem(
                                      value: pr,
                                      child: Text(pr,
                                          style: const TextStyle(fontSize: 13),
                                          overflow: TextOverflow.ellipsis)))
                                  .toList(),
                              onChanged: (val) => setState(() =>
                                  _selectedPriority = (val == 'All Priorities')
                                      ? ''
                                      : (val ?? '')),
                            ),
                          ],
                        ),
                      ),
                      SizedBox(
                        width: 170,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('CATEGORY',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.grey)),
                            const SizedBox(height: 6),
                            DropdownButtonFormField<String>(
                              value: _selectedCategory.isEmpty
                                  ? 'All Categories'
                                  : _selectedCategory,
                              isExpanded: true,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor: theme.colorScheme.surface,
                                contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 10),
                                border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide: BorderSide(
                                        color: Colors.grey.withOpacity(0.2))),
                              ),
                              items: _categories
                                  .map((cat) => DropdownMenuItem(
                                      value: cat,
                                      child: Text(cat,
                                          style: const TextStyle(fontSize: 13),
                                          overflow: TextOverflow.ellipsis)))
                                  .toList(),
                              onChanged: (val) => setState(() =>
                                  _selectedCategory = (val == 'All Categories')
                                      ? ''
                                      : (val ?? '')),
                            ),
                          ],
                        ),
                      ),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          ElevatedButton.icon(
                            onPressed: () => setState(() {}),
                            icon: const Icon(Icons.search, size: 16),
                            label: const Text('Filter'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: theme.colorScheme.surface,
                              foregroundColor: theme.colorScheme.onSurface,
                              side: BorderSide(
                                  color: theme.colorScheme.onSurface
                                      .withOpacity(0.2)),
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 16, vertical: 14),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12)),
                              textStyle:
                                  const TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                          const SizedBox(width: 8),
                          TextButton(
                            onPressed: _clearFilters,
                            child: const Text('Clear'),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Tickets Table / Cards List
          ticketsAsync.when(
            data: (tickets) {
              final filtered = tickets.where((t) {
                final query = _searchQuery.trim().toLowerCase();
                final matchQuery = query.isEmpty ||
                    t.title.toLowerCase().contains(query) ||
                    t.ticketNumber.toLowerCase().contains(query) ||
                    t.category.toLowerCase().contains(query) ||
                    t.description.toLowerCase().contains(query);
                final matchStatus = _selectedStatus.isEmpty ||
                    _selectedStatus.toLowerCase().startsWith('all') ||
                    t.status.toLowerCase() == _selectedStatus.toLowerCase() ||
                    t.status.toLowerCase().replaceAll(' ', '') == _selectedStatus.toLowerCase().replaceAll(' ', '');
                final matchPriority = _selectedPriority.isEmpty ||
                    _selectedPriority.toLowerCase().startsWith('all') ||
                    t.priority.toLowerCase() == _selectedPriority.toLowerCase();
                final matchCategory = _selectedCategory.isEmpty ||
                    _selectedCategory.toLowerCase().startsWith('all') ||
                    t.category.toLowerCase() == _selectedCategory.toLowerCase() ||
                    t.category.toLowerCase().contains(_selectedCategory.toLowerCase());
                return matchQuery &&
                    matchStatus &&
                    matchPriority &&
                    matchCategory;
              }).toList();

              final displayTickets = filtered.isNotEmpty
                  ? filtered
                  : (_searchQuery.isEmpty && _activeFiltersCount == 0 ? tickets : <Ticket>[]);

              if (displayTickets.isEmpty) {
                return _activeFiltersCount > 0 || _searchQuery.isNotEmpty
                    ? EmptyState.noSearchResults(onClearFilters: _clearFilters)
                    : EmptyState.noTickets(
                        onCreateTicket: () => _showCreateTicketDialog(context),
                      );
              }

              // On Mobile: Render Native Cards
              if (!isDesktop) {
                return ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: displayTickets.length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final ticket = displayTickets[index];
                    return _buildMobileTicketCard(context, ticket, theme);
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
                    columnSpacing: 24,
                    horizontalMargin: 24,
                    showCheckboxColumn: false,
                    columns: const [
                      DataColumn(label: Text('NUMBER')),
                      DataColumn(label: Text('TITLE')),
                      DataColumn(label: Text('CATEGORY')),
                      DataColumn(label: Text('CREATED BY')),
                      DataColumn(label: Text('PRIORITY')),
                      DataColumn(label: Text('STATUS')),
                      DataColumn(label: Text('CREATED AT')),
                      DataColumn(label: Text('ASSIGNED TO')),
                    ],
                    rows: displayTickets.map((ticket) {
                      return DataRow(
                        onSelectChanged: (_) => _openTicketDetails(ticket),
                        cells: [
                          DataCell(Text(ticket.ticketNumber,
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF2563EB)))),
                          DataCell(SizedBox(
                              width: 200,
                              child: Text(ticket.title,
                                  overflow: TextOverflow.ellipsis))),
                          DataCell(Text(ticket.category)),
                          DataCell(Text(ticket.createdBy ?? 'Unknown')),
                          DataCell(_buildPriorityBadge(ticket.priority)),
                          DataCell(_buildStatusBadge(ticket.status)),
                          DataCell(Text(DateFormat('dd MMM yyyy, HH:mm')
                              .format(ticket.createdAt))),
                          DataCell(Text(ticket.assignedTo ?? 'Unassigned',
                              style: const TextStyle(
                                  fontWeight: FontWeight.w500))),
                        ],
                      );
                    }).toList(),
                  ),
                ),
              );
            },
            loading: () => const TicketListSkeletonLoader(),
            error: (err, stack) => ErrorState(
              title: 'Tickets failed to load',
              error: err,
              onRetry: () => ref.refresh(ticketsProvider),
            ),
          ),
        ],
      ),
    ));
    } catch (e) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, size: 48, color: Colors.red),
              const SizedBox(height: 12),
              const Text('Tickets failed to load',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text('$e',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 13, color: Colors.grey)),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: () => ref.refresh(ticketsProvider),
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }
  }

  Widget _buildMobileTicketCard(
      BuildContext context, Ticket ticket, ThemeData theme) {
    return InkWell(
      onTap: () => _openTicketDetails(ticket),
      borderRadius: BorderRadius.circular(16),
      child: AppCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row: Number + Priority + Status
            Wrap(
              alignment: WrapAlignment.spaceBetween,
              crossAxisAlignment: WrapCrossAlignment.center,
              spacing: 8,
              runSpacing: 6,
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6366F1).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    ticket.ticketNumber,
                    style: const TextStyle(
                      color: Color(0xFF6366F1),
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                    ),
                  ),
                ),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    _buildPriorityBadge(ticket.priority),
                    _buildStatusBadge(ticket.status),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Title
            Text(
              ticket.title.isNotEmpty ? ticket.title : 'Untitled Ticket',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: theme.colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 10),

            // Details Row
            Row(
              children: [
                Icon(Icons.folder_outlined,
                    size: 14, color: theme.colorScheme.onSurface.withOpacity(0.5)),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    ticket.category,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        fontSize: 12,
                        color: theme.colorScheme.onSurface.withOpacity(0.7)),
                  ),
                ),
                const SizedBox(width: 8),
                Icon(Icons.calendar_today,
                    size: 14, color: theme.colorScheme.onSurface.withOpacity(0.5)),
                const SizedBox(width: 4),
                Text(
                  DateFormat('dd MMM, HH:mm').format(ticket.createdAt),
                  style: TextStyle(
                      fontSize: 12,
                      color: theme.colorScheme.onSurface.withOpacity(0.7)),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPriorityBadge(String priority) {
    Color color;
    switch (priority.toLowerCase()) {
      case 'low':
        color = const Color(0xFF10B981);
        break;
      case 'medium':
        color = const Color(0xFFF59E0B);
        break;
      case 'high':
        color = const Color(0xFFEF4444);
        break;
      case 'critical':
        color = const Color(0xFF991B1B);
        break;
      default:
        color = Colors.grey;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        priority.toUpperCase(),
        style:
            TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.w800),
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color color;
    switch (status.toLowerCase().replaceAll(' ', '')) {
      case 'open':
        color = const Color(0xFF1D4ED8);
        break;
      case 'assigned':
        color = const Color(0xFF7E22CE);
        break;
      case 'inprogress':
        color = const Color(0xFFC2410C);
        break;
      case 'resolved':
        color = const Color(0xFF0F766E);
        break;
      default:
        color = Colors.grey;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        status.toUpperCase(),
        style:
            TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.w800),
      ),
    );
  }
}
