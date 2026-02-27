package com.roosterplanner.app.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.preferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.dataStore by preferencesDataStore(name = "roosterplanner")

class ConfigStore(private val context: Context) {
    private val KEY_URL = preferencesKey<String>("base_url")
    private val KEY_USER = preferencesKey<String>("username")
    private val KEY_PASS = preferencesKey<String>("app_password")

    val config: Flow<AuthConfig?> = context.dataStore.data.map { prefs ->
        val url = prefs[KEY_URL]
        val user = prefs[KEY_USER]
        val pass = prefs[KEY_PASS]
        if (url != null && user != null && pass != null) AuthConfig(url, user, pass) else null
    }

    suspend fun save(config: AuthConfig) {
        context.dataStore.edit { prefs ->
            prefs[KEY_URL] = config.baseUrl
            prefs[KEY_USER] = config.username
            prefs[KEY_PASS] = config.appPassword
        }
    }

    suspend fun clear() {
        context.dataStore.edit { prefs ->
            prefs.clear()
        }
    }
}
