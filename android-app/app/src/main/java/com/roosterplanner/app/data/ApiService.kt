package com.roosterplanner.app.data

import com.squareup.moshi.Moshi
import okhttp3.Credentials
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface ApiService {
    @GET("wp-json/roosterplanner/v1/me")
    suspend fun me(): MeResponse

    @GET("wp-json/roosterplanner/v1/schedules")
    suspend fun schedules(
        @Query("start_date") startDate: String,
        @Query("end_date") endDate: String
    ): List<ScheduleItem>

    @GET("wp-json/roosterplanner/v1/notifications")
    suspend fun notifications(
        @Query("limit") limit: Int = 50,
        @Query("unread_only") unreadOnly: Int = 0
    ): List<NotificationItem>

    @POST("wp-json/roosterplanner/v1/notifications/{id}/read")
    suspend fun markNotificationRead(@Path("id") id: Long)

    @GET("wp-json/roosterplanner/v1/availability")
    suspend fun availability(
        @Query("month") month: String,
        @Query("location_id") locationId: Long
    ): AvailabilityResponse

    @POST("wp-json/roosterplanner/v1/availability")
    suspend fun upsertAvailability(@Body body: AvailabilityUpsertRequest)
}

object ApiClient {
    fun build(config: AuthConfig, enableLogging: Boolean = false): ApiService {
        val authHeader = Credentials.basic(config.username, config.appPassword)

        val authInterceptor = Interceptor { chain ->
            val request = chain.request().newBuilder()
                .addHeader("Authorization", authHeader)
                .build()
            chain.proceed(request)
        }

        val builder = OkHttpClient.Builder()
            .addInterceptor(authInterceptor)

        if (enableLogging) {
            val logger = HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC }
            builder.addInterceptor(logger)
        }

        val client = builder.build()
        val moshi = Moshi.Builder().build()

        val retrofit = Retrofit.Builder()
            .baseUrl(config.baseUrl.ensureTrailingSlash())
            .client(client)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()

        return retrofit.create(ApiService::class.java)
    }
}

private fun String.ensureTrailingSlash(): String = if (this.endsWith('/')) this else "$this/"
